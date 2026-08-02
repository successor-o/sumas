<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
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
            ->with('course', 'lecturer')
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
        $courseIds = $user->courses->pluck('id');
        
        $lectures = Lecture::whereIn('course_id', $courseIds)
            ->with('course', 'lecturer')
            ->latest('scheduled_date')
            ->get()
            ->map(fn ($l) => $this->formatLecture($l));

        return response()->json(['lectures' => $lectures]);
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
            'course_id' => $a->course_id,
            'course_name' => $a->course->name ?? null,
            'course_code' => $a->course->code ?? null,
            'lecturer_id' => $a->lecturer_id,
            'lecturer_name' => $a->lecturer->name ?? null,
            'lecture_date' => $a->lecture_date,
            'status' => $a->status,
            'notes' => $a->notes,
        ];
    }

    private function formatLecture(Lecture $l): array
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
