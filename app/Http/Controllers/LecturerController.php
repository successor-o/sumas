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
            'totp_secret' => Str::random(32),
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
     * Live attendance code for the QR modal (polled by the frontend).
     * GET /api/lecturer/lectures/{id}/live
     *
     * Lazily rotates the token when its interval has elapsed, and returns the
     * current QR url, the rotating 6-digit code and their expiry timestamps.
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

        $lecture->ensureFreshToken();

        return response()->json([
            'token' => $lecture->token,
            'previous_token' => $lecture->previous_token,
            'qr_url' => url('/attend/' . $lecture->token),
            'code' => $lecture->totp(),
            'code_expires_at' => $lecture->totpExpiresAt()->toIso8601String(),
            'rotation_expires_at' => $lecture->token_rotated_at
                ->addSeconds((int) config('attendance.rotation_seconds', 60))
                ->toIso8601String(),
            'rotation_seconds' => (int) config('attendance.rotation_seconds', 60),
            'gps_required' => $lecture->gps_required,
            'attendance_count' => Attendance::where('lecture_id', $lecture->id)->count(),
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
     * department-wide lectures, so those students can scan the QR too).
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
            // token is lecturer-only — the student-facing payload never includes it
            'token' => $l->token,
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
