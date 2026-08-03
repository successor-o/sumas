<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Department;
use App\Models\Document;
use App\Models\Lecture;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /**
     * Get student dashboard data (fresh from DB).
     * GET /api/student/dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user()->fresh();

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Update student profile (phone, gender, DOB, state, faculty).
     * PUT /api/student/profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'phone' => 'sometimes|nullable|string|min:8|max:20',
            'gender' => 'sometimes|nullable|string|max:30',
            'dob' => 'sometimes|nullable|date',
            'state_of_origin' => 'sometimes|nullable|string|max:60',
            'faculty' => 'sometimes|nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }
        $user->update($validator->validated());
        $user->update(['status' => 'Pending']);

        return response()->json([
            'message' => 'Profile updated successfully. Status set to pending verification.',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    /**
     * List the authenticated student's uploaded documents.
     * GET /api/student/documents
     */
    public function documents(Request $request): JsonResponse
    {
        $docs = $request->user()->documents()->latest()->get()->map(fn (Document $d) => $this->formatDocument($d));

        return response()->json(['documents' => $docs]);
    }

    /**
     * Upload a document (school ID, admission letter, clearance, passport photo...).
     * POST /api/student/documents  (multipart: doc_type + file)
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $allowed = ['school-id', 'admission', 'clearance', 'nat-id', 'pp-1', 'pp-2', 'pp-3'];

        $validator = Validator::make($request->all() + $request->allFiles(), [
            'doc_type' => ['required', 'string', 'in:'.implode(',', $allowed)],
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $type = $request->input('doc_type');
        $file = $request->file('file');
        $stored = $file->store("documents/{$user->id}", 'public');

        $doc = Document::create([
            'user_id' => $user->id,
            'doc_type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => basename($stored),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        $user->increment('docs_count');

        return response()->json([
            'message' => 'Document uploaded successfully.',
            'document' => $this->formatDocument($doc),
        ], 201);
    }

    /**
     * Delete one of the authenticated student's documents.
     * DELETE /api/student/documents/{id}
     */
    public function deleteDocument(Request $request, int $id): JsonResponse
    {
        $doc = Document::where('user_id', $request->user()->id)->findOrFail($id);

        Storage::disk('public')->delete("documents/{$doc->user_id}/{$doc->stored_name}");
        $doc->delete();
        $request->user()->decrement('docs_count');

        return response()->json(['message' => 'Document deleted.']);
    }

    /* ── Private helpers ── */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'matric' => $user->matric,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
            'dept' => $user->dept,
            'level' => $user->level,
            'gender' => $user->gender,
            'dob' => $user->dob,
            'state_of_origin' => $user->state_of_origin,
            'faculty' => $user->faculty,
            'verified' => $user->verified,
            'face_enrolled_at' => $user->face_enrolled_at,
            'face_photo' => $user->face_photo ? Storage::disk('public')->url($user->face_photo) : null,
            'face_ready' => $user->hasFaceEnrolled(),
            'docs_count' => $user->docs_count,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function formatDocument(Document $d): array
    {
        return [
            'id' => $d->id,
            'doc_type' => $d->doc_type,
            'label' => $d->label(),
            'original_name' => $d->original_name,
            'url' => $d->url(),
            'mime' => $d->mime,
            'size' => $d->size,
            'created_at' => $d->created_at,
        ];
    }

    /**
     * Public status check by matric number.
     * GET /api/student/status?matric=SUMAS/CS/2023/001
     *
     * Matric numbers contain slashes, so the value travels as a query
     * parameter (encoded slashes in URL paths are rejected by the router).
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'matric' => 'required|string',
        ]);

        $matric = $validated['matric'];

        $user = User::where('matric', strtoupper(trim($matric)))
            ->where('role', 'student')
            ->first();

        if (! $user) {
            return response()->json(['message' => 'No registration found for that matric number.'], 404);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'matric' => $user->matric,
                'dept' => $user->dept,
                'level' => $user->level,
                'status' => $user->status,
                'verified' => $user->verified,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Get courses for the student portal.
     * GET /api/student/courses
     *
     * By default this returns courses from the student's department/faculty
     * based on their registration. When a department_id (or faculty_id) filter
     * is supplied, it returns the matching course catalogue instead.
     */
    public function courses(Request $request): JsonResponse
    {
        $user = $request->user();
        $departmentId = (int) $request->query('department_id', 0);
        $facultyId    = (int) $request->query('faculty_id', 0);

        if ($departmentId || $facultyId) {
            $query = Course::query()->where('is_active', true);

            if ($departmentId) {
                $query->where('department_id', $departmentId);
            } elseif ($facultyId) {
                $query->whereHas('department', fn ($q) => $q->where('faculty_id', $facultyId));
            }

            $courses = $query->latest()->get()->map(fn ($c) => $this->formatCourse($c));

            return response()->json(['courses' => $courses]);
        }

        // Get courses from student's department based on their registration
        $department = \App\Models\Department::where('name', $user->dept)->first();
        
        if ($department) {
            $courses = Course::where('department_id', $department->id)
                ->where('is_active', true)
                ->latest()
                ->get()
                ->map(fn ($c) => $this->formatCourse($c));
        } else {
            // Fallback to enrolled courses if department not found
            $courses = $user->courses->map(fn ($c) => $this->formatCourse($c));
        }

        return response()->json(['courses' => $courses]);
    }

    /**
     * Get student's attendance records.
     * GET /api/student/attendance
     */
    public function attendance(Request $request): JsonResponse
    {
        $user = $request->user();
        $attendances = Attendance::where('student_id', $user->id)
            ->with('course', 'lecturer', 'lecture')
            ->latest('lecture_date')
            ->get()
            ->map(fn ($a) => $this->formatAttendance($a));

        return response()->json(['attendances' => $attendances]);
    }

    /**
     * Get student's lectures and notifications.
     * GET /api/student/lectures
     */
    public function lectures(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get courses from student's department
        $department = Department::where('name', $user->dept)->first();
        
        if ($department) {
            // Show lectures from all courses in the student's department
            $courseIds = Course::where('department_id', $department->id)
                ->where('is_active', true)
                ->pluck('id');
        } else {
            // Fallback to enrolled courses if department not found
            $courseIds = $user->courses->pluck('id');
        }
        
        $lectures = Lecture::whereIn('course_id', $courseIds)
            ->with('course', 'lecturer')
            ->latest('scheduled_date')
            ->get();

        // Which lectures has this student already marked? Drives the
        // "✓ Attended" badge on the portal.
        $attended = Attendance::where('student_id', $user->id)
            ->whereIn('lecture_id', $lectures->pluck('id'))
            ->pluck('status', 'lecture_id');

        return response()->json([
            'lectures' => $lectures->map(fn ($l) => $this->formatLecture($l, $attended[$l->id] ?? null)),
        ]);
    }

    /**
     * Get student's notifications.
     * GET /api/student/notifications
     */
    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()
            ->with('lecture.course')
            ->get()
            ->map(fn ($n) => $this->formatNotification($n));

        return response()->json(['notifications' => $notifications]);
    }

    /**
     * Public lecture info for the QR check-in page (no token leak).
     * GET /api/attend/{token}
     */
    public function lectureInfo(string $token): JsonResponse
    {
        $lecture = Lecture::where('token', $token)->with('course', 'lecturer')->first();

        if (! $lecture) {
            return response()->json(['message' => 'Invalid or expired attendance code.'], 404);
        }

        return response()->json([
            'lecture' => [
                'id' => $lecture->id,
                'title' => $lecture->title,
                'course_code' => $lecture->course->code ?? null,
                'course_name' => $lecture->course->name ?? null,
                'lecturer_name' => $lecture->lecturer->name ?? null,
                'scheduled_date' => $lecture->scheduled_date,
                'is_active' => $lecture->is_active,
                'attendance_enabled' => $lecture->attendance_enabled,
                'gps_required' => $lecture->gps_required,
                'attendance_score' => $lecture->attendance_score,
            ],
        ]);
    }

    /**
     * Mark attendance via a face scan.
     * POST /api/student/attend  { token, embedding, device_id, latitude?, longitude? }
     *
     * The student's browser computes a 128-dim FaceNet embedding of their live
     * face (face-api.js) and sends it here; it is compared against the embedding
     * captured at enrollment. The QR token only identifies the lecture.
     *
     * Guard rails:
     *  - Lecture must exist, be active and have smart attendance enabled.
     *  - QR token rotates every rotation_seconds; the previous token stays valid
     *    for one rotation window (grace).
     *  - The student must be face-enrolled (has a stored embedding).
     *  - The live embedding must match the enrolled one (cosine similarity ≥
     *    the configured threshold).
     *  - Optional GPS geofence when the lecture requires location.
     *  - A device that already marked ANY student for this lecture is locked out
     *    (stops one phone being passed around the class).
     *  - A student can only mark once per lecture.
     *  - The student must be part of the lecture audience (enrolled or in the
     *    course's department).
     */
    public function scanAttendance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'embedding' => ['required', 'array', 'size:128'],
            'embedding.*' => ['numeric'],
            'device_id' => 'required|string|max:64',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $token = $request->token;
        $lecture = Lecture::where(fn ($q) => $q->where('token', $token)->orWhere('previous_token', $token))->first();

        if (! $lecture) {
            return response()->json(['message' => 'Invalid or expired attendance link. Ask your lecturer to show the QR code again.'], 404);
        }

        // May rotate lazily; the matched token stays valid as the previous one.
        $lecture->ensureFreshToken();

        if (! $lecture->matchesToken($token)) {
            return response()->json(['message' => 'This attendance link has expired. Ask your lecturer to refresh the QR code.'], 422);
        }

        return $this->completeFaceCheckin($request, $request->user(), $lecture);
    }

    /**
     * Face check-in from the student portal, by lecture id.
     * POST /api/student/lectures/{id}/face-checkin
     * { embedding, device_id, latitude?, longitude? }
     *
     * Lets a student check in straight from their dashboard for a live lecture
     * without needing to scan the lecturer's QR link (no token is exposed in
     * the lectures payload). Shares every guard with scanAttendance.
     */
    public function faceCheckin(Request $request, int $lectureId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'embedding' => ['required', 'array', 'size:128'],
            'embedding.*' => ['numeric'],
            'device_id' => 'required|string|max:64',
            'latitude' => 'sometimes|nullable|numeric|between:-90,90',
            'longitude' => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $lecture = Lecture::find($lectureId);

        if (! $lecture) {
            return response()->json(['message' => 'Lecture not found.'], 404);
        }

        return $this->completeFaceCheckin($request, $request->user(), $lecture);
    }

    /**
     * Shared face-scan check-in: identity verification + attendance creation.
     */
    private function completeFaceCheckin(Request $request, User $user, Lecture $lecture): JsonResponse
    {
        if (! $lecture->is_active) {
            return response()->json(['message' => 'This lecture has ended. Attendance is closed.'], 422);
        }

        if (! $lecture->attendance_enabled) {
            return response()->json(['message' => 'Face check-in is not enabled for this lecture.'], 422);
        }

        // Optional GPS geofence — the student must be near the lecture venue.
        if ($lecture->gps_required) {
            $lat = $request->input('latitude');
            $lng = $request->input('longitude');

            if ($lat === null || $lng === null) {
                return response()->json(['message' => 'Location access is required for this lecture. Allow location and try again.'], 422);
            }

            $radius = (float) config('attendance.gps_radius_meters', 200);
            $distance = $lecture->distanceTo((float) $lat, (float) $lng);

            if ($distance > $radius) {
                return response()->json(['message' => 'You are outside the lecture location (' . round($radius) . 'm). Move closer and try again.'], 422);
            }
        }

        // Biometric identity: the student must be enrolled, and the live scan
        // must match their enrolled FaceNet embedding.
        if (! $user->hasFaceEnrolled()) {
            return response()->json(['message' => 'Your face is not enrolled yet. Visit the ICT office so an administrator can enroll your face before checking in.'], 422);
        }

        $embedding = array_values(array_map('floatval', $request->input('embedding')));
        if (! $user->verifyFace($embedding)) {
            return response()->json(['message' => 'Face did not match the enrolled identity. Make sure the camera is clear and try again.'], 422);
        }

        // One scan per student first (friendlier message on a re-scan), then
        // one scan per device — a device that already marked anyone is locked.
        if (Attendance::where('lecture_id', $lecture->id)
            ->where('student_id', $user->id)
            ->exists()) {
            return response()->json(['message' => 'You have already marked your attendance for this lecture.'], 422);
        }

        if (Attendance::where('lecture_id', $lecture->id)
            ->where('device_id', $request->device_id)
            ->exists()) {
            return response()->json(['message' => 'This device has already been used to mark attendance for this lecture.'], 422);
        }

        // Eligibility — enrolled in the course, or in the course's department.
        $course = $lecture->course;
        $dept = $course->department_id ? Department::find($course->department_id) : null;
        $deptName = $dept?->name ?: $course->department;

        $eligible = $course->students()->where('users.id', $user->id)->exists()
            || ($deptName && $user->dept === $deptName);

        if (! $eligible) {
            return response()->json(['message' => 'You are not registered for this lecture.'], 403);
        }

        $attendance = Attendance::create([
            'student_id' => $user->id,
            'lecture_id' => $lecture->id,
            'course_id' => $course->id,
            'lecturer_id' => $lecture->lecturer_id,
            'lecture_date' => $lecture->scheduled_date,
            'status' => 'present',
            'source' => 'face',
            'device_id' => $request->device_id,
        ]);

        return response()->json([
            'message' => 'Face verified — attendance marked successfully. Enjoy the lecture!',
            'attendance' => $this->formatAttendance($attendance),
        ], 201);
    }

    /**
     * Mark notification as read.
     * PUT /api/student/notifications/{id}/read
     */
    public function markNotificationRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    private function formatCourse(Course $c): array
    {
        return [
            'id' => $c->id,
            'code' => $c->code,
            'name' => $c->name,
            'department' => $c->department,
            'description' => $c->description,
            'credit_units' => $c->credit_units,
            'level' => $c->level,
            'is_active' => $c->is_active,
        ];
    }

    private function formatAttendance(Attendance $a): array
    {
        return [
            'id' => $a->id,
            'lecture_id' => $a->lecture_id,
            'lecture_title' => $a->lecture->title ?? null,
            'course_id' => $a->course_id,
            'course_name' => $a->course->name ?? null,
            'course_code' => $a->course->code ?? null,
            'lecturer_id' => $a->lecturer_id,
            'lecturer_name' => $a->lecturer->name ?? null,
            'lecture_date' => $a->lecture_date,
            'status' => $a->status,
            'notes' => $a->notes,
            'source' => $a->source,
            // Marks awarded for attending — from the lecture the student attended.
            'attendance_score' => $a->lecture->attendance_score ?? null,
        ];
    }

    private function formatLecture(Lecture $l, ?string $attended = null): array
    {
        return [
            'id' => $l->id,
            'course_id' => $l->course_id,
            'course_name' => $l->course->name ?? null,
            'course_code' => $l->course->code ?? null,
            'lecturer_id' => $l->lecturer_id,
            'lecturer_name' => $l->lecturer->name ?? null,
            'title' => $l->title,
            'content' => $l->content,
            'scheduled_date' => $l->scheduled_date,
            'ended_at' => $l->ended_at,
            'is_active' => $l->is_active,
            'attendance_enabled' => $l->attendance_enabled,
            'gps_required' => $l->gps_required,
            'attendance_score' => $l->attendance_score,
            // 'present' | 'late' | null — the student's own record for this lecture
            'attended' => $attended,
            'created_at' => $l->created_at,
        ];
    }

    private function formatNotification(Notification $n): array
    {
        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'message' => $n->message,
            'read' => $n->read,
            'read_at' => $n->read_at,
            'created_at' => $n->created_at,
            'lecture' => $n->lecture ? [
                'id' => $n->lecture->id,
                'title' => $n->lecture->title,
                'course_name' => $n->lecture->course->name ?? null,
            ] : null,
        ];
    }
}
