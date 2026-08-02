<?php

namespace App\Http\Controllers;

use App\Models\AcademicLevel;
use App\Models\Course;
use App\Models\Department;
use App\Models\Document;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Dashboard statistics.
     * GET /api/admin/stats
     */
    public function stats(): JsonResponse
    {
        $students = User::students()->get();

        $byDept = $students->groupBy('dept')->map->count()->sortDesc();
        $byLevel = $students->groupBy('level')->map->count()->sortDesc();

        $recent = $students->sortByDesc('created_at')
            ->take(10)
            ->values()
            ->map(fn ($u) => $this->formatStudent($u));

        return response()->json([
            'total' => $students->count(),
            'pending' => $students->where('status', 'Pending')->count(),
            'approved' => $students->where('status', 'Approved')->count(),
            'rejected' => $students->where('status', 'Rejected')->count(),
            'verified' => $students->where('verified', true)->count(),
            'by_dept' => $byDept,
            'by_level' => $byLevel,
            'recent' => $recent,
        ]);
    }

    /**
     * List students with optional status filter.
     * GET /api/admin/students?status=Pending
     */
    public function students(Request $request): JsonResponse
    {
        $query = User::students()->latest();

        if ($request->filled('status') && in_array($request->status, ['Pending', 'Approved', 'Rejected'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                    ->orWhere('matric', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('dept', 'like', "%{$q}%");
            });
        }

        $students = $query->get()->map(fn ($u) => $this->formatStudent($u));

        return response()->json(['students' => $students]);
    }

    /**
     * Get single student (with uploaded documents).
     * GET /api/admin/students/{id}
     */
    public function getStudent(int $id): JsonResponse
    {
        $user = User::students()->with('documents')->findOrFail($id);

        return response()->json([
            'user' => $this->formatStudent($user),
            'documents' => $user->documents->map(fn (Document $d) => [
                'id' => $d->id,
                'doc_type' => $d->doc_type,
                'label' => $d->label(),
                'original_name' => $d->original_name,
                'url' => $d->url(),
                'mime' => $d->mime,
                'size' => $d->size,
                'created_at' => $d->created_at,
            ])->values(),
        ]);
    }

    /**
     * Update student status.
     * PUT /api/admin/students/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:Pending,Approved,Rejected',
        ]);

        $user = User::students()->findOrFail($id);
        $user->update(['status' => $request->status]);

        return response()->json([
            'message' => "Student status updated to {$request->status}.",
            'user' => $this->formatStudent($user->fresh()),
        ]);
    }

    /**
     * Record facial registration — admin captures the student's face via webcam
     * and uploads the image here (multipart: face_image).
     * POST /api/admin/students/{id}/face-register
     */
    public function faceRegister(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all() + $request->allFiles(), [
            'face_image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'A webcam face image is required.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::students()->findOrFail($id);

        // Remove any previously enrolled face photo.
        if ($user->face_photo) {
            Storage::disk('public')->delete($user->face_photo);
        }

        $ext = strtolower($request->file('face_image')->getClientOriginalExtension() ?: 'jpg');
        $stored = $request->file('face_image')->storeAs(
            'faces',
            $user->id.'-'.now()->format('Ymd-His').'.'.$ext,
            'public'
        );

        $user->update([
            'verified' => true,
            'face_enrolled_at' => now(),
            'face_photo' => $stored,
        ]);

        return response()->json([
            'message' => "Facial registration recorded for {$user->name}.",
            'user' => $this->formatStudent($user->fresh()),
        ]);
    }

    /**
     * Change the admin's own password.
     * POST /api/admin/password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $admin = $request->user();

        if (! Hash::check($request->current_password, $admin->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $admin->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /**
     * Create a new student.
     * POST /api/admin/students
     */
    public function createStudent(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:120',
            'matric' => 'required|string|unique:users,matric|max:50',
            'email' => 'required|email|unique:users,email|max:120',
            'phone' => 'required|string|min:8|max:20',
            'password' => ['required', 'string', Password::min(6)],
            'department_id' => 'required|exists:departments,id',
            'level' => 'required|string|max:20',
            'gender' => 'nullable|string|max:30',
            'dob' => 'nullable|date',
            'state_of_origin' => 'nullable|string|max:60',
            'status' => 'sometimes|in:Pending,Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        
        // Get department and faculty info
        $department = Department::find($data['department_id']);
        $faculty = $department?->faculty;

        $user = User::create([
            'name' => $data['name'],
            'matric' => strtoupper(trim($data['matric'])),
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'status' => $data['status'] ?? 'Pending',
            'dept' => $department?->name,
            'level' => $data['level'],
            'gender' => $data['gender'] ?? null,
            'dob' => $data['dob'] ?? null,
            'state_of_origin' => $data['state_of_origin'] ?? null,
            'faculty' => $faculty?->name,
            'verified' => false,
            'docs_count' => 0,
        ]);

        return response()->json([
            'message' => 'Student created successfully.',
            'user' => $this->formatStudent($user),
        ], 201);
    }

    /**
     * Update a student.
     * PUT /api/admin/students/{id}
     */
    public function updateStudent(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|min:2|max:120',
            'matric' => 'sometimes|required|string|unique:users,matric,'.$id.'|max:50',
            'email' => 'sometimes|required|email|unique:users,email,'.$id.'|max:120',
            'phone' => 'sometimes|required|string|min:8|max:20',
            'password' => 'sometimes|string|min:6',
            'department_id' => 'sometimes|required|exists:departments,id',
            'level' => 'sometimes|required|string|max:20',
            'gender' => 'nullable|string|max:30',
            'dob' => 'nullable|date',
            'state_of_origin' => 'nullable|string|max:60',
            'status' => 'sometimes|in:Pending,Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $user = User::students()->findOrFail($id);
        $data = $validator->validated();

        // Handle department/faculty update
        if (isset($data['department_id'])) {
            $department = Department::find($data['department_id']);
            $faculty = $department?->faculty;
            $data['dept'] = $department?->name;
            $data['faculty'] = $faculty?->name;
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Student updated successfully.',
            'user' => $this->formatStudent($user->fresh()),
        ]);
    }

    /**
     * Delete a student.
     * DELETE /api/admin/students/{id}
     */
    public function deleteStudent(int $id): JsonResponse
    {
        $user = User::students()->findOrFail($id);
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Student record deleted successfully.']);
    }

    /**
     * Export all students as CSV.
     * GET /api/admin/export
     */
    public function exportCsv(): Response
    {
        $students = User::students()->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sumas-students-'.now()->format('Y-m-d').'.csv"',
        ];

        $columns = ['ID', 'Name', 'Matric', 'Email', 'Phone', 'Department', 'Level', 'Gender', 'State', 'Verified', 'Status', 'Submitted'];

        $callback = function () use ($students, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($students as $u) {
                fputcsv($file, [
                    $u->id, $u->name, $u->matric, $u->email, $u->phone ?? '',
                    $u->dept, $u->level, $u->gender ?? '', $u->state_of_origin ?? '',
                    $u->verified ? 'Yes' : 'No', $u->status,
                    $u->created_at?->format('d M Y') ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get all courses.
     * GET /api/admin/courses
     */
    public function courses(): JsonResponse
    {
        $courses = Course::with('lecturers')->latest()->get()->map(fn ($c) => $this->formatCourse($c));
        return response()->json(['courses' => $courses]);
    }

    /**
     * Create a new course.
     * POST /api/admin/courses
     */
    public function createCourse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:20|unique:courses,code',
            'name' => 'required|string|max:200',
            'department_id' => 'required|exists:departments,id',
            'description' => 'nullable|string',
            'credit_units' => 'required|integer|min:0|max:10',
            'level' => 'required|integer|in:100,200,300,400,500,600',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $course = Course::create($validator->validated());

        return response()->json([
            'message' => 'Course created successfully.',
            'course' => $this->formatCourse($course->fresh()),
        ], 201);
    }

    /**
     * Update a course.
     * PUT /api/admin/courses/{id}
     */
    public function updateCourse(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|required|string|max:20|unique:courses,code,'.$id,
            'name' => 'sometimes|required|string|max:200',
            'department_id' => 'sometimes|required|exists:departments,id',
            'description' => 'nullable|string',
            'credit_units' => 'sometimes|required|integer|min:0|max:10',
            'level' => 'sometimes|required|integer|in:100,200,300,400,500,600',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $course = Course::findOrFail($id);
        $course->update($validator->validated());

        return response()->json([
            'message' => 'Course updated successfully.',
            'course' => $this->formatCourse($course->fresh()),
        ]);
    }

    /**
     * Delete a course.
     * DELETE /api/admin/courses/{id}
     */
    public function deleteCourse(int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        $course->delete();

        return response()->json(['message' => 'Course deleted successfully.']);
    }

    /**
     * Assign lecturer to course.
     * POST /api/admin/courses/{courseId}/assign-lecturer
     */
    public function assignLecturer(Request $request, int $courseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lecturer_id' => 'required|exists:lecturers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $course = Course::findOrFail($courseId);
        $lecturer = Lecturer::findOrFail($request->lecturer_id);

        if ($course->lecturers()->where('lecturers.id', $lecturer->id)->exists()) {
            return response()->json(['message' => 'Lecturer already assigned to this course.'], 400);
        }

        $course->lecturers()->attach($lecturer->id);

        return response()->json([
            'message' => 'Lecturer assigned successfully.',
            'course' => $this->formatCourse($course->fresh()),
        ]);
    }

    /**
     * Remove lecturer from course.
     * DELETE /api/admin/courses/{courseId}/lecturers/{lecturerId}
     */
    public function removeLecturer(int $courseId, int $lecturerId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        $course->lecturers()->detach($lecturerId);

        return response()->json(['message' => 'Lecturer removed successfully.']);
    }

    /**
     * Get all lecturers.
     * GET /api/admin/lecturers
     */
    public function lecturers(): JsonResponse
    {
        $lecturers = Lecturer::latest()->get()->map(fn ($l) => $this->formatLecturer($l));
        return response()->json(['lecturers' => $lecturers]);
    }

    /**
     * Get all departments.
     * GET /api/admin/departments
     */
    public function departments(): JsonResponse
    {
        $departments = \App\Models\Department::where('is_active', true)->get();
        return response()->json(['departments' => $departments]);
    }

    /**
     * Create a new lecturer.
     * POST /api/admin/lecturers
     */
    public function createLecturer(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:lecturers,email|max:120',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|min:8|max:20',
            'department_id' => 'required|exists:departments,id',
            'bio' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        // `department` is a NOT NULL string column on lecturers — keep it in
        // sync with the selected department so department-scoped queries
        // (e.g. lecturers viewing their students) keep working.
        $department = Department::find($request->department_id);

        $lecturer = Lecturer::create([
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'department' => $department?->name,
            'department_id' => $request->department_id,
            'bio' => $request->bio,
        ]);

        return response()->json([
            'message' => 'Lecturer created successfully.',
            'lecturer' => $this->formatLecturer($lecturer),
        ], 201);
    }

    /**
     * Update a lecturer.
     * PUT /api/admin/lecturers/{id}
     */
    public function updateLecturer(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:120',
            'email' => 'sometimes|required|email|unique:lecturers,email,'.$id.'|max:120',
            'phone' => 'nullable|string|min:8|max:20',
            'department_id' => 'sometimes|required|exists:departments,id',
            'bio' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $lecturer = Lecturer::findOrFail($id);
        $data = $validator->validated();

        // Keep the legacy `department` string in sync when the department changes.
        if (isset($data['department_id'])) {
            $data['department'] = Department::find($data['department_id'])?->name;
        }

        $lecturer->update($data);

        return response()->json([
            'message' => 'Lecturer updated successfully.',
            'lecturer' => $this->formatLecturer($lecturer->fresh()),
        ]);
    }

    /**
     * Delete a lecturer.
     * DELETE /api/admin/lecturers/{id}
     */
    public function deleteLecturer(int $id): JsonResponse
    {
        $lecturer = Lecturer::findOrFail($id);
        $lecturer->tokens()->delete();
        $lecturer->delete();

        return response()->json(['message' => 'Lecturer deleted successfully.']);
    }

    /**
     * Get all faculties (with department counts).
     * GET /api/admin/faculties
     */
    public function faculties(): JsonResponse
    {
        $faculties = Faculty::withCount('departments')->latest()->get();
        return response()->json(['faculties' => $faculties]);
    }

    /**
     * Create a new faculty.
     * POST /api/admin/faculties
     */
    public function createFaculty(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200|unique:faculties,name',
            'code' => 'required|string|max:20|unique:faculties,code',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $faculty = Faculty::create($validator->validated());

        return response()->json([
            'message' => 'Faculty created successfully.',
            'faculty' => $faculty->fresh(),
        ], 201);
    }

    /**
     * Update a faculty.
     * PUT /api/admin/faculties/{id}
     */
    public function updateFaculty(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:200|unique:faculties,name,'.$id,
            'code' => 'sometimes|required|string|max:20|unique:faculties,code,'.$id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $faculty = Faculty::findOrFail($id);
        $faculty->update($validator->validated());

        return response()->json([
            'message' => 'Faculty updated successfully.',
            'faculty' => $faculty->fresh(),
        ]);
    }

    /**
     * Delete a faculty.
     * DELETE /api/admin/faculties/{id}
     */
    public function deleteFaculty(int $id): JsonResponse
    {
        $faculty = Faculty::findOrFail($id);
        $faculty->delete();

        return response()->json(['message' => 'Faculty deleted successfully.']);
    }

    /**
     * Get all departments for management (incl. inactive, with counts + faculty).
     * GET /api/admin/departments/all
     */
    public function allDepartments(): JsonResponse
    {
        $departments = Department::with('faculty')
            ->withCount(['courses', 'lecturers'])
            ->latest()
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'description' => $d->description,
                'faculty_id' => $d->faculty_id,
                'faculty_name' => $d->faculty?->name,
                'courses_count' => $d->courses_count,
                'lecturers_count' => $d->lecturers_count,
                'is_active' => $d->is_active,
                'created_at' => $d->created_at,
                'updated_at' => $d->updated_at,
            ]);

        return response()->json(['departments' => $departments]);
    }

    /**
     * Create a new department.
     * POST /api/admin/departments
     */
    public function createDepartment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200|unique:departments,name',
            'code' => 'required|string|max:20|unique:departments,code',
            'faculty_id' => 'required|exists:faculties,id',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $department = Department::create($validator->validated());

        return response()->json([
            'message' => 'Department created successfully.',
            'department' => $this->formatDepartment($department->fresh()),
        ], 201);
    }

    /**
     * Update a department.
     * PUT /api/admin/departments/{id}
     */
    public function updateDepartment(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:200|unique:departments,name,'.$id,
            'code' => 'sometimes|required|string|max:20|unique:departments,code,'.$id,
            'faculty_id' => 'sometimes|required|exists:faculties,id',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $department = Department::findOrFail($id);
        $department->update($validator->validated());

        return response()->json([
            'message' => 'Department updated successfully.',
            'department' => $this->formatDepartment($department->fresh()),
        ]);
    }

    /**
     * Delete a department.
     * DELETE /api/admin/departments/{id}
     */
    public function deleteDepartment(int $id): JsonResponse
    {
        $department = Department::findOrFail($id);
        $department->delete();

        return response()->json(['message' => 'Department deleted successfully.']);
    }

    /**
     * Get all academic levels.
     * GET /api/admin/academic-levels
     */
    public function academicLevels(): JsonResponse
    {
        $levels = AcademicLevel::orderBy('sort_order')->get();
        return response()->json(['levels' => $levels]);
    }

    /**
     * Create a new academic level.
     * POST /api/admin/academic-levels
     */
    public function createAcademicLevel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:120|unique:academic_levels,name',
            'code' => 'required|string|max:10|unique:academic_levels,code',
            'sort_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $level = AcademicLevel::create($validator->validated());

        return response()->json([
            'message' => 'Academic level created successfully.',
            'level' => $level->fresh(),
        ], 201);
    }

    /**
     * Update an academic level.
     * PUT /api/admin/academic-levels/{id}
     */
    public function updateAcademicLevel(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:120|unique:academic_levels,name,'.$id,
            'code' => 'sometimes|required|string|max:10|unique:academic_levels,code,'.$id,
            'sort_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $level = AcademicLevel::findOrFail($id);
        $level->update($validator->validated());

        return response()->json([
            'message' => 'Academic level updated successfully.',
            'level' => $level->fresh(),
        ]);
    }

    /**
     * Delete an academic level.
     * DELETE /api/admin/academic-levels/{id}
     */
    public function deleteAcademicLevel(int $id): JsonResponse
    {
        $level = AcademicLevel::findOrFail($id);
        $level->delete();

        return response()->json(['message' => 'Academic level deleted successfully.']);
    }

    /* ── Private ── */
    private function formatStudent(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'matric' => $u->matric,
            'email' => $u->email,
            'phone' => $u->phone,
            'dept' => $u->dept,
            'level' => $u->level,
            'gender' => $u->gender,
            'dob' => $u->dob,
            'state_of_origin' => $u->state_of_origin,
            'faculty' => $u->faculty,
            'verified' => $u->verified,
            'face_enrolled_at' => $u->face_enrolled_at,
            'face_photo' => $u->face_photo ? Storage::disk('public')->url($u->face_photo) : null,
            'docs_count' => $u->docs_count,
            'status' => $u->status,
            'created_at' => $u->created_at,
            'updated_at' => $u->updated_at,
        ];
    }

    private function formatDepartment(Department $d): array
    {
        return [
            'id' => $d->id,
            'name' => $d->name,
            'code' => $d->code,
            'description' => $d->description,
            'faculty_id' => $d->faculty_id,
            'faculty_name' => $d->faculty?->name,
            'courses_count' => $d->courses()->count(),
            'lecturers_count' => $d->lecturers()->count(),
            'is_active' => $d->is_active,
            'created_at' => $d->created_at,
            'updated_at' => $d->updated_at,
        ];
    }

    private function formatCourse(Course $c): array
    {
        // NB: `department` is a string column on courses, so the department
        // relation cannot be read via `$c->department` — resolve it explicitly.
        $dept = $c->department_id ? Department::find($c->department_id) : null;

        return [
            'id' => $c->id,
            'code' => $c->code,
            'name' => $c->name,
            'department' => $c->department,
            'department_id' => $c->department_id,
            'department_name' => $dept?->name,
            'faculty_name' => $dept?->faculty?->name,
            'description' => $c->description,
            'credit_units' => $c->credit_units,
            'level' => $c->level,
            'is_active' => $c->is_active,
            'lecturers' => $c->lecturers->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'email' => $l->email,
                'department' => $l->department,
                'department_id' => $l->department_id,
            ]),
            'created_at' => $c->created_at,
            'updated_at' => $c->updated_at,
        ];
    }

    private function formatLecturer(Lecturer $l): array
    {
        // NB: `department` is a string column on lecturers, so the department
        // relation cannot be read via `$l->department` — resolve it explicitly.
        $dept = $l->department_id ? Department::find($l->department_id) : null;

        return [
            'id' => $l->id,
            'name' => $l->name,
            'email' => $l->email,
            'phone' => $l->phone,
            'department' => $l->department,
            'department_id' => $l->department_id,
            'department_name' => $dept?->name,
            'faculty_name' => $dept?->faculty?->name,
            'bio' => $l->bio,
            'is_active' => $l->is_active,
            'courses_count' => $l->courses->count(),
            'created_at' => $l->created_at,
            'updated_at' => $l->updated_at,
        ];
    }
}
