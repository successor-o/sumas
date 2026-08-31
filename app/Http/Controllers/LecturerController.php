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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
            'attendance_enabled' => 'sometimes|boolean',
            'gps_required' => 'sometimes|boolean',
            'latitude' => 'required_if:gps_required,true|nullable|numeric|between:-90,90',
            'longitude' => 'required_if:gps_required,true|nullable|numeric|between:-180,180',
            'attendance_score' => 'sometimes|nullable|numeric|min:0|max:999.99',
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
            // Random token drives the QR code — only ever returned to the lecturer.
            'token' => Str::random(20),
            'token_rotated_at' => now(),
            'is_active' => true,
            'attendance_enabled' => $request->boolean('attendance_enabled', true),
            'gps_required' => $request->boolean('gps_required'),
            'latitude' => $request->filled('latitude') ? $request->input('latitude') : null,
            'longitude' => $request->filled('longitude') ? $request->input('longitude') : null,
            // Optional marks awarded to each student who attends this lecture.
            'attendance_score' => $request->filled('attendance_score') ? $request->input('attendance_score') : null,
        ]);

        // Notify students about the new lecture (enrolled in the course, or in
        // the course's department — the same audience that can see it on the portal)
        $students = $this->lectureStudents($lecture);
        foreach ($students as $student) {
            Notification::create([
                'user_id' => $student->id,
                'lecture_id' => $lecture->id,
                'type' => 'info',
                'title' => 'New Lecture Created',
                'message' => "A new lecture '{$lecture->title}' has been created for {$course->code} - {$course->name}. Scheduled: " . $lecture->scheduled_date->format('M d, Y \a\t g:i A'),
            ]);
        }

        return response()->json([
            'message' => 'Lecture created successfully. Students have been notified.',
            'lecture' => $this->formatLecture($lecture),
        ], 201);
    }

    /**
     * Live lecture info for the lecturer dashboard.
     * GET /api/lecturer/lectures/{id}/live
     *
     * Returns the current attendance count for the lecture.
     */
    public function liveCode(Request $request, int $id): JsonResponse
    {
        $lecturer = $request->user();
        $lecture = Lecture::where('id', $id)
            ->where('lecturer_id', $lecturer->id)
            ->first();

        if (! $lecture) {
            return response()->json(['message' => 'Lecture not found or not yours.'], 404);
        }

        return response()->json([
            'attendance_count' => Attendance::where('lecture_id', $lecture->id)->count(),
        ]);
    }

    /**
     * Scan a student's face — two-step flow:
     *
     * STEP 1 — PREVIEW (confirm=false, default)
     *   POST /api/lecturer/lectures/{id}/scan-student  { embedding }
     *   Returns the matched student + match_score but does NOT create attendance.
     *   The lecturer sees the name and decides whether to proceed.
     *
     * STEP 2 — CONFIRM (confirm=true)
     *   POST /api/lecturer/lectures/{id}/scan-student  { student_id, confirm: true }
     *   Creates the attendance record for the student shown in step 1.
     *
     * Guard rails:
     *  - The lecture must belong to the lecturer, be active and have smart
     *    attendance enabled.
     *  - Only approved, face-enrolled students in the lecture audience can be matched.
     *  - A student can only be marked once per lecture.
     */
    public function scanStudent(Request $request, int $id): JsonResponse
    {
        $lecturer = $request->user();
        $lecture = Lecture::where('id', $id)
            ->where('lecturer_id', $lecturer->id)
            ->first();

        if (! $lecture) {
            return response()->json(['message' => 'Lecture not found or not yours.'], 404);
        }

        if (! $lecture->is_active) {
            return response()->json(['message' => 'This lecture has ended. Attendance is closed.'], 422);
        }

        if (! $lecture->attendance_enabled) {
            return response()->json(['message' => 'Face scan check-in is not enabled for this lecture.'], 422);
        }

        /* ── STEP 2: CONFIRM — mark attendance for a previously identified student ── */
        if ($request->boolean('confirm')) {
            $validator = Validator::make($request->all(), [
                'student_id' => ['required', 'exists:users,id'],
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
            }

            $studentId = (int) $request->input('student_id');
            $student = User::students()->where('id', $studentId)->first();

            if (! $student || ! $student->hasFaceEnrolled()) {
                return response()->json(['message' => 'Student not found or face not enrolled.'], 422);
            }

            // Ensure the student is in the lecture audience.
            $inAudience = $this->lectureStudents($lecture)->contains('id', $studentId);
            if (! $inAudience) {
                return response()->json(['message' => 'Student is not in this lecture audience.'], 422);
            }

            // One scan per student.
            if (Attendance::where('lecture_id', $lecture->id)
                ->where('student_id', $studentId)
                ->exists()) {
                return response()->json([
                    'message' => $student->name . ' has already been marked for this lecture.',
                    'student' => $this->formatStudent($student),
                ], 422);
            }

            $attendance = Attendance::create([
                'student_id'   => $studentId,
                'lecture_id'   => $lecture->id,
                'course_id'    => $lecture->course_id,
                'lecturer_id'  => $lecturer->id,
                'lecture_date' => $lecture->scheduled_date,
                'status'       => 'present',
                'source'       => 'lecturer',
            ]);

            return response()->json([
                'message'     => 'Attendance marked for ' . $student->name . '.',
                'student'     => $this->formatStudent($student),
                'attendance'  => $this->formatAttendance($attendance),
            ], 201);
        }

        /* ── STEP 1: PREVIEW — identify the student without marking attendance ── */
        $validator = Validator::make($request->all(), [
            'embedding'  => ['required', 'array', 'size:128'],
            'embedding.*' => ['numeric'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        // Only face-enrolled students in the lecture audience can be matched.
        $audience = $this->lectureStudents($lecture)
            ->filter(fn (User $s) => $s->hasFaceEnrolled())
            ->values();

        if ($audience->isEmpty()) {
            return response()->json(['message' => 'No students with an enrolled face are registered for this lecture.'], 422);
        }

        $embedding = array_values(array_map('floatval', $request->input('embedding')));

        // Reject garbage embeddings — face-api.js can produce all-zero or
        // degenerate descriptors when the model files fail to load, when the
        // detector fires on a non-face region, or when the image is too blurry.
        if (! User::isValidFaceEmbedding($embedding)) {
            return response()->json([
                'message' => 'The face image quality is too low. Ask the student to face the camera directly with good lighting and try again.',
            ], 422);
        }

        // Require at least 2 faces detected in the frame so the lecturer
        // is actually pointing at someone (not a wall / empty room).
        $faceCount = (int) $request->input('face_count', 1);
        if ($faceCount < 1) {
            return response()->json([
                'message' => 'No face detected. Ask the student to face the camera and try again.',
            ], 422);
        }
        if ($faceCount > 1) {
            return response()->json([
                'message' => 'Multiple faces detected. Point the camera at one student at a time.',
            ], 422);
        }

        $threshold = (float) config('attendance.face_similarity_threshold', 0.70);

        // Find the best-matching enrolled face in the audience.
        $best = null;
        $bestScore = 0.0;
        foreach ($audience as $student) {
            $score = $student->faceSimilarity($embedding);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $student;
            }
        }

        if (! $best || $bestScore < $threshold) {
            return response()->json(['message' => 'Face not recognized. Ask the student to face the camera and try again.'], 422);
        }

        // Check if already marked.
        $alreadyMarked = Attendance::where('lecture_id', $lecture->id)
            ->where('student_id', $best->id)
            ->exists();

        return response()->json([
            'message'      => $alreadyMarked
                ? $best->name . ' has already been marked for this lecture.'
                : 'Is this ' . $best->name . '?',
            'student'      => $this->formatStudent($best),
            'match_score'  => round($bestScore, 4),
            'already_marked' => $alreadyMarked,
        ]);
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
                'lecture_id' => $lecture->id,
            ],
            [
                'course_id' => $lecture->course_id,
                'lecturer_id' => $lecturer->id,
                'lecture_date' => $lecture->scheduled_date,
                'status' => $request->status,
                'notes' => $request->notes,
                'source' => 'manual',
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

        $attendances = Attendance::where('lecture_id', $lecture->id)
            ->with('student', 'lecture')
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

        // Students who marked attendance (present or late) are safe — everyone
        // else in the lecture audience is treated as absent and notified.
        $course = Course::find($lecture->course_id);
        $students = $this->lectureStudents($lecture);

        $marked = Attendance::where('lecture_id', $lecture->id)
            ->whereIn('status', ['present', 'late'])
            ->pluck('student_id')
            ->toArray();

        foreach ($students as $student) {
            if (in_array($student->id, $marked)) {
                continue;
            }

            Notification::create([
                'user_id' => $student->id,
                'lecture_id' => $lecture->id,
                'type' => 'warning',
                'title' => 'Missed Lecture',
                'message' => "You missed the lecture '{$lecture->title}' for {$course->code} - {$course->name}. Please review the course materials.",
            ]);
        }

        return response()->json([
            'message' => 'Lecture ended successfully. Notifications sent to absent students.',
            'lecture' => $this->formatLecture($lecture->fresh()),
        ]);
    }

    /* ── Private helpers ── */
    /**
     * The audience for a lecture: approved students enrolled in the course,
     * plus approved students in the course's department (the portal shows
     * department-wide lectures, so those students can be scanned too).
     */
    private function lectureStudents(Lecture $lecture): Collection
    {
        $course = $lecture->course;

        $enrolled = $course->students()->where('status', 'Approved')->get();

        $dept = $course->department_id ? Department::find($course->department_id) : null;
        $deptName = $dept?->name ?: $course->department;
        $byDept = $deptName
            ? User::students()->where('status', 'Approved')->where('dept', $deptName)->get()
            : collect();

        return $enrolled->concat($byDept)->unique('id')->values();
    }

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
            'attendance_enabled' => $l->attendance_enabled,
            'gps_required' => $l->gps_required,
            'attendance_score' => $l->attendance_score,
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
            'source' => $a->source,
            'attendance_score' => $a->lecture->attendance_score ?? null,
        ];
    }
}
