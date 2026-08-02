<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Department;
use App\Models\Lecture;
use App\Models\Lecturer;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LecturerController extends Controller
{
    /**
     * Lecturer dashboard statistics.
     * GET /api/lecturer/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $lecturer = $request->user();
        $courses = $lecturer->courses;
        
        // Count only approved students from the lecturer's department
        $studentQuery = User::students()->where('status', 'Approved');
        $deptName = $this->departmentName($lecturer);
        if ($deptName) {
            $studentQuery->where('dept', $deptName);
        }
        $studentCount = $studentQuery->count();

        $recentLectures = Lecture::where('lecturer_id', $lecturer->id)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'lecturer' => [
                'id' => $lecturer->id,
                'name' => $lecturer->name,
                'email' => $lecturer->email,
                'department' => $this->departmentName($lecturer),
            ],
            'courses_count' => $courses->count(),
            'students_count' => $studentCount,
            'recent_lectures' => $recentLectures->map(fn ($l) => $this->formatLecture($l)),
        ]);
    }

    /**
     * Get students (filtered by department and approved status).
     * GET /api/lecturer/students
     */
    public function students(Request $request): JsonResponse
    {
        $lecturer = $request->user();
        $query = User::students()->where('status', 'Approved');

        $deptName = $this->departmentName($lecturer);
        if ($deptName) {
            $query->where('dept', $deptName);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                    ->orWhere('matric', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $students = $query->latest()->get()->map(fn ($u) => $this->formatStudent($u));

        return response()->json(['students' => $students]);
    }

    /**
     * Get lecturer's assigned courses.
     * GET /api/lecturer/courses
     */
    public function courses(Request $request): JsonResponse
    {
        $lecturer = $request->user();
        $courses = $lecturer->courses->map(fn ($c) => $this->formatCourse($c));

        return response()->json(['courses' => $courses]);
    }

    /**
     * Create a new lecture/announcement.
     * POST /api/lecturer/lectures
     */
    public function createLecture(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'scheduled_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $lecturer = $request->user();

        // Verify lecturer is assigned to this course
        $course = $lecturer->courses()->where('courses.id', $request->input('course_id'))->first();
        if (! $course) {
            return response()->json(['message' => 'You are not assigned to this course.'], 403);
        }

        $lecture = Lecture::create([
            'course_id' => $request->input('course_id'),
            'lecturer_id' => $lecturer->id,
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'scheduled_date' => $request->input('scheduled_date'),
        ]);

        return response()->json([
            'message' => 'Lecture created successfully.',
            'lecture' => $this->formatLecture($lecture),
        ], 201);
    }

    /**
     * Get lecturer's lectures.
     * GET /api/lecturer/lectures
     */
    public function lectures(Request $request): JsonResponse
    {
        $lecturer = $request->user();
        $query = Lecture::where('lecturer_id', $lecturer->id);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $lectures = $query->latest()->get()->map(fn ($l) => $this->formatLecture($l));

        return response()->json(['lectures' => $lectures]);
    }

    /**
     * Record attendance for a lecture.
     * POST /api/lecturer/attendance
     */
    public function recordAttendance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lecture_id' => 'required|exists:lectures,id',
            'student_id' => 'required|exists:users,id',
            'status' => 'required|in:present,absent,late',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $lecturer = $request->user();
        $lecture = Lecture::where('id', $request->lecture_id)
            ->where('lecturer_id', $lecturer->id)
            ->first();

        if (! $lecture) {
            return response()->json(['message' => 'Lecture not found or not yours.'], 404);
        }

        $student = User::students()->where('id', $request->student_id)->first();
        if (! $student || $student->status !== 'Approved') {
            return response()->json(['message' => 'Student not found or not approved.'], 404);
        }

        $attendance = Attendance::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'course_id' => $lecture->course_id,
                'lecture_date' => $lecture->scheduled_date,
            ],
            [
                'lecturer_id' => $lecturer->id,
                'status' => $request->status,
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'message' => 'Attendance recorded successfully.',
            'attendance' => $this->formatAttendance($attendance),
        ]);
    }

    /**
     * Get attendance for a specific lecture.
     * GET /api/lecturer/attendance/{lectureId}
     */
    public function getAttendance(Request $request, int $lectureId): JsonResponse
    {
        $lecturer = $request->user();
        $lecture = Lecture::where('id', $lectureId)
            ->where('lecturer_id', $lecturer->id)
            ->first();

        if (! $lecture) {
            return response()->json(['message' => 'Lecture not found or not yours.'], 404);
        }

        $attendances = Attendance::where('course_id', $lecture->course_id)
            ->where('lecture_date', $lecture->scheduled_date)
            ->with('student')
            ->get()
            ->map(fn ($a) => $this->formatAttendance($a));

        return response()->json(['attendances' => $attendances]);
    }

    /**
     * End a lecture and send notifications to absent students.
     * POST /api/lecturer/lectures/{id}/end
     */
    public function endLecture(Request $request, int $id): JsonResponse
    {
        $lecturer = $request->user();
        $lecture = Lecture::where('id', $id)
            ->where('lecturer_id', $lecturer->id)
            ->first();

        if (! $lecture) {
            return response()->json(['message' => 'Lecture not found or not yours.'], 404);
        }

        $lecture->update([
            'is_active' => false,
            'ended_at' => now(),
        ]);

        // Get students enrolled in the course
        $course = Course::find($lecture->course_id);
        $students = $course->students()->where('status', 'Approved')->get();

        // Get students who were absent or late
        $absentStudents = Attendance::where('course_id', $lecture->course_id)
            ->where('lecture_date', $lecture->scheduled_date)
            ->whereIn('status', ['absent', 'late'])
            ->pluck('student_id')
            ->toArray();

        // Send notifications to absent students
        foreach ($students as $student) {
            if (in_array($student->id, $absentStudents)) {
                Notification::create([
                    'user_id' => $student->id,
                    'lecture_id' => $lecture->id,
                    'type' => 'warning',
                    'title' => 'Missed Lecture',
                    'message' => "You missed the lecture '{$lecture->title}' for {$course->code} - {$course->name}. Please review the course materials.",
                ]);
            }
        }

        return response()->json([
            'message' => 'Lecture ended successfully. Notifications sent to absent students.',
            'lecture' => $this->formatLecture($lecture->fresh()),
        ]);
    }

    /* ── Private helpers ── */
    /**
     * Resolve the lecturer's department name.
     *
     * `department_id` is the source of truth — the legacy `department` string
     * column is often empty for lecturers created through the admin panel, so
     * comparing students' `dept` against it would silently return no students.
     */
    private function departmentName(Lecturer $lecturer): ?string
    {
        if ($lecturer->department_id) {
            $dept = Department::find($lecturer->department_id);
            if ($dept) {
                return $dept->name;
            }
        }

        $department = $lecturer->department;

        return is_string($department) ? $department : null;
    }

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
            'status' => $u->status,
            'verified' => $u->verified,
        ];
    }

    private function formatCourse(Course $c): array
    {
        return [
            'id' => $c->id,
            'code' => $c->code,
            'name' => $c->name,
            'department' => $c->department,
            'credit_units' => $c->credit_units,
            'level' => $c->level,
            'is_active' => $c->is_active,
        ];
    }

    private function formatLecture(Lecture $l): array
    {
        return [
            'id' => $l->id,
            'course_id' => $l->course_id,
            'title' => $l->title,
            'content' => $l->content,
            'scheduled_date' => $l->scheduled_date,
            'ended_at' => $l->ended_at,
            'is_active' => $l->is_active,
            'created_at' => $l->created_at,
        ];
    }

    private function formatAttendance(Attendance $a): array
    {
        return [
            'id' => $a->id,
            'student_id' => $a->student_id,
            'student_name' => $a->student->name ?? null,
            'student_matric' => $a->student->matric ?? null,
            'course_id' => $a->course_id,
            'lecture_date' => $a->lecture_date,
            'status' => $a->status,
            'notes' => $a->notes,
        ];
    }
}
