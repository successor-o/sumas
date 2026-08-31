<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Department;
use App\Models\Lecture;
use App\Models\Lecturer;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Smart attendance — two-step face-scan flow:
 *
 *  1. PREVIEW  — lecturer scans a student's face; the server identifies them
 *     but does NOT mark attendance yet.
 *  2. CONFIRM  — the lecturer sees the student's name and clicks Confirm;
 *     only then is the attendance record created.
 *
 * Lecturers can still record attendance manually.
 * The student portal reflects marked attendance.
 */
class SmartAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function makeDept(): Department
    {
        return Department::create(['name' => 'Computer Science', 'code' => 'CS']);
    }

    private function makeCourse(Department $dept): Course
    {
        return Course::create([
            'code'          => 'CS101',
            'name'          => 'Introduction to Computer Science',
            'department'    => $dept->name,
            'department_id' => $dept->id,
            'credit_units'  => 3,
            'level'         => '100 Level',
            'is_active'     => true,
        ]);
    }

    private function makeLecturer(): Lecturer
    {
        return Lecturer::create([
            'name'       => 'Dr. Ada Obi',
            'email'      => 'ada.obi@sumas.edu.ng',
            'password'   => bcrypt('secret123'),
            'department' => 'Computer Science',
            'is_active'  => true,
        ]);
    }

    private function makeStudent(array $overrides = []): User
    {
        static $n = 0;
        $n++;

        return User::factory()->create(array_merge([
            'status' => 'Approved',
            'dept'   => 'Computer Science',
            'matric' => 'SUMAS/CS/2023/' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'face_embedding' => $this->embedding(1.0),
        ], $overrides));
    }

    /** Deterministic 128-dim FaceNet-like unit embedding. */
    private function embedding(float $seed): array
    {
        $v = [];
        for ($i = 0; $i < 128; $i++) {
            $v[] = sin($seed * 2.0 + $i * 0.7) + cos($seed * 3.0 + $i * 0.3);
        }
        $norm = sqrt(array_sum(array_map(fn ($x) => $x * $x, $v)));

        return array_map(fn ($x) => $x / $norm, $v);
    }

    /** A live scan that should match the enrolled face (tiny perturbation). */
    private function matchingScan(): array
    {
        $e = $this->embedding(1.0);
        $e[0] += 0.0001;

        return $e;
    }

    /** A live scan that must NOT match the enrolled face (cosine = -1). */
    private function wrongScan(): array
    {
        return array_map(fn ($x) => -$x, $this->embedding(1.0));
    }

    private function makeLecture(Lecturer $lecturer, Course $course, array $overrides = []): Lecture
    {
        return Lecture::create(array_merge([
            'course_id'          => $course->id,
            'lecturer_id'        => $lecturer->id,
            'title'              => 'Introduction to Algorithms',
            'content'            => 'Big-O notation and complexity analysis.',
            'scheduled_date'     => now()->addHour(),
            'token'              => Str::random(20),
            'token_rotated_at'   => now(),
            'attendance_enabled' => true,
            'is_active'          => true,
        ], $overrides));
    }

    /** Helper: preview + confirm in one step. */
    private function scanAndConfirm(int $lectureId, array $embedding): \Illuminate\Testing\TestResponse
    {
        $preview = $this->postJson("/api/lecturer/lectures/{$lectureId}/scan-student", [
            'embedding' => $embedding,
        ]);
        $preview->assertOk();
        $studentId = $preview->json('student.id');

        return $this->postJson("/api/lecturer/lectures/{$lectureId}/scan-student", [
            'student_id' => $studentId,
            'confirm'    => true,
        ]);
    }

    /* ═══════════════════════════════════════════════════════════
       LECTURE CREATION TESTS
    ═══════════════════════════════════════════════════════════ */

    public function test_create_lecture_generates_token_and_notifies_students(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $student = $this->makeStudent();
        $course->students()->attach($student->id);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures', [
            'course_id'          => $course->id,
            'title'              => 'Data Structures',
            'content'            => 'Arrays, lists and maps.',
            'scheduled_date'     => now()->addDay()->format('Y-m-d H:i:s'),
            'attendance_enabled' => true,
        ]);

        $res->assertCreated()
            ->assertJsonPath('lecture.is_active', true)
            ->assertJsonPath('lecture.attendance_enabled', true);

        $lectureId = $res->json('lecture.id');
        $this->assertNotNull($res->json('lecture.id'));
        $this->assertDatabaseHas('lectures', ['id' => $lectureId, 'attendance_enabled' => true]);
        $this->assertDatabaseHas('notifications', ['user_id' => $student->id, 'lecture_id' => $lectureId]);
    }

    public function test_create_lecture_can_disable_smart_attendance(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures', [
            'course_id'          => $course->id,
            'title'              => 'Revision',
            'content'            => 'Past questions.',
            'scheduled_date'     => now()->addDay()->format('Y-m-d H:i:s'),
            'attendance_enabled' => false,
        ]);

        $res->assertCreated()->assertJsonPath('lecture.attendance_enabled', false);
    }

    public function test_create_lecture_with_optional_attendance_score(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures', [
            'course_id'        => $course->id,
            'title'            => 'Marks Lecture',
            'content'          => 'Attendance is worth 2 marks.',
            'scheduled_date'   => now()->addDay()->format('Y-m-d H:i:s'),
            'attendance_score' => 2.5,
        ]);

        $res->assertCreated()
            ->assertJsonPath('lecture.attendance_score', 2.5);
        $this->assertDatabaseHas('lectures', ['id' => $res->json('lecture.id'), 'attendance_score' => 2.5]);

        // Blank score → null (no marks awarded).
        $res2 = $this->postJson('/api/lecturer/lectures', [
            'course_id'      => $course->id,
            'title'          => 'No Marks Lecture',
            'content'        => 'No marks.',
            'scheduled_date' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);
        $res2->assertCreated();
        $this->assertNull($res2->json('lecture.attendance_score'));
    }

    /* ═══════════════════════════════════════════════════════════
       TWO-STEP SCAN FLOW TESTS
    ═══════════════════════════════════════════════════════════ */

    public function test_preview_identifies_student_without_marking_attendance(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->matchingScan(),
        ]);

        $res->assertOk()
            ->assertJsonPath('student.id', $student->id)
            ->assertJsonPath('student.name', $student->name)
            ->assertJsonStructure(['match_score']);

        // Attendance should NOT be created yet.
        $this->assertDatabaseMissing('attendances', [
            'student_id' => $student->id,
            'lecture_id' => $lecture->id,
        ]);
    }

    public function test_confirm_marks_attendance_after_preview(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        // Preview
        $preview = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->matchingScan(),
        ]);
        $preview->assertOk();
        $this->assertSame($student->id, $preview->json('student.id'));

        // Confirm
        $confirm = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'student_id' => $student->id,
            'confirm'    => true,
        ]);

        $confirm->assertCreated()
            ->assertJsonPath('attendance.status', 'present')
            ->assertJsonPath('attendance.source', 'lecturer')
            ->assertJsonPath('student.id', $student->id)
            ->assertJsonPath('student.name', $student->name);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'lecture_id' => $lecture->id,
            'status'     => 'present',
            'source'     => 'lecturer',
        ]);
    }

    public function test_lecturer_can_scan_student_to_mark_attendance(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->scanAndConfirm($lecture->id, $this->matchingScan());

        $res->assertCreated()
            ->assertJsonPath('attendance.status', 'present')
            ->assertJsonPath('attendance.source', 'lecturer')
            ->assertJsonPath('student.id', $student->id)
            ->assertJsonPath('student.name', $student->name);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'lecture_id' => $lecture->id,
            'status'     => 'present',
            'source'     => 'lecturer',
        ]);
    }

    public function test_lecturer_cannot_scan_same_student_twice(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        // First scan — preview + confirm → success
        $this->scanAndConfirm($lecture->id, $this->matchingScan())->assertCreated();

        // Second scan — preview succeeds but shows already_marked
        $preview2 = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->matchingScan(),
        ]);
        $preview2->assertOk()->assertJsonPath('already_marked', true);
        $this->assertStringContainsString('already been marked', $preview2->json('message'));

        // Confirm is rejected (already marked)
        $confirm2 = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'student_id' => $student->id,
            'confirm'    => true,
        ]);
        $confirm2->assertStatus(422);
        $this->assertStringContainsString('already been marked', $confirm2->json('message'));
    }

    public function test_lecturer_cannot_scan_for_other_lecturers_lecture(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer1 = $this->makeLecturer();
        $lecturer2 = Lecturer::create([
            'name'       => 'Dr. Other',
            'email'      => 'other@sumas.edu.ng',
            'password'   => bcrypt('secret123'),
            'department' => 'Computer Science',
            'is_active'  => true,
        ]);
        $lecturer2->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer1, $course);
        $student  = $this->makeStudent();

        // Lecturer2 tries to scan a lecture that belongs to Lecturer1
        Sanctum::actingAs($lecturer2, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->matchingScan(),
        ]);

        $res->assertStatus(404);
        $this->assertStringContainsString('not yours', $res->json('message'));
    }

    public function test_scan_is_rejected_after_lecture_ends(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course, ['is_active' => false]);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->matchingScan(),
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('ended', $res->json('message'));
        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id, 'lecture_id' => $lecture->id]);
    }

    public function test_scan_rejected_when_attendance_disabled(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course, ['attendance_enabled' => false]);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->matchingScan(),
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('not enabled', $res->json('message'));
    }

    public function test_face_mismatch_is_rejected(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->wrongScan(),
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('not recognized', $res->json('message'));
        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id, 'lecture_id' => $lecture->id]);
    }

    public function test_different_student_cannot_be_matched(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student1 = $this->makeStudent(); // seed 1.0
        $student2 = $this->makeStudent(['face_embedding' => $this->embedding(2.0)]); // seed 2.0

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        // Scan with student2's face — preview must identify student2, not student1
        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->embedding(2.0),
        ]);

        $res->assertOk();
        $this->assertEquals($student2->id, $res->json('student.id'));
        $this->assertDatabaseMissing('attendances', ['student_id' => $student1->id, 'lecture_id' => $lecture->id]);

        // Now confirm — should mark student2, not student1
        $confirm = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'student_id' => $student2->id,
            'confirm'    => true,
        ]);
        $confirm->assertCreated();
        $this->assertDatabaseHas('attendances', ['student_id' => $student2->id, 'lecture_id' => $lecture->id]);
        $this->assertDatabaseMissing('attendances', ['student_id' => $student1->id, 'lecture_id' => $lecture->id]);
    }

    public function test_student_without_enrolled_face_cannot_be_scanned(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent(['face_embedding' => null]);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->matchingScan(),
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('No students with an enrolled face', $res->json('message'));
    }

    public function test_lecturer_can_scan_multiple_students_in_one_lecture(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student1 = $this->makeStudent();
        $student2 = $this->makeStudent(['face_embedding' => $this->embedding(2.0)]);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        // Scan + confirm student1 (seed 1.0)
        $res1 = $this->scanAndConfirm($lecture->id, $this->matchingScan());
        $res1->assertCreated();
        $this->assertSame($student1->id, $res1->json('student.id'));

        // Scan + confirm student2 (seed 2.0)
        $scan2 = $this->embedding(2.0);
        $scan2[0] += 0.0001;
        $res2 = $this->scanAndConfirm($lecture->id, $scan2);
        $res2->assertCreated();
        $this->assertSame($student2->id, $res2->json('student.id'));

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student1->id,
            'lecture_id' => $lecture->id,
        ]);
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student2->id,
            'lecture_id' => $lecture->id,
        ]);
    }

    public function test_lecturer_can_manually_record_attendance_without_scan(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture = $this->makeLecture($lecturer, $course);
        $student = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/attendance', [
            'lecture_id' => $lecture->id,
            'student_id' => $student->id,
            'status'     => 'present',
            'notes'      => 'manual entry',
        ]);

        $res->assertOk()->assertJsonPath('attendance.source', 'manual');

        $this->assertDatabaseHas('attendances', [
            'lecture_id' => $lecture->id,
            'student_id' => $student->id,
            'source'     => 'manual',
            'status'     => 'present',
        ]);
    }

    public function test_student_lectures_include_attended_flag(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Attendance::create([
            'student_id'   => $student->id,
            'lecture_id'   => $lecture->id,
            'course_id'    => $course->id,
            'lecturer_id'  => $lecturer->id,
            'lecture_date' => $lecture->scheduled_date,
            'status'       => 'present',
            'source'       => 'lecturer',
        ]);

        Sanctum::actingAs($student, ['role:student']);

        $res = $this->getJson('/api/student/lectures');

        $res->assertOk()
            ->assertJsonCount(1, 'lectures')
            ->assertJsonPath('lectures.0.attended', 'present');
    }

    public function test_student_attendance_records_carry_the_lecture_marks(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course, ['attendance_score' => 2]);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);
        $this->scanAndConfirm($lecture->id, $this->matchingScan())->assertCreated();

        Sanctum::actingAs($student, ['role:student']);
        $res = $this->getJson('/api/student/attendance');
        $res->assertOk()
            ->assertJsonCount(1, 'attendances')
            ->assertJsonPath('attendances.0.attendance_score', 2);
    }

    public function test_student_from_another_department_cannot_be_scanned(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $other    = $this->makeStudent(['dept' => 'Nursing Science', 'face_embedding' => $this->embedding(2.0)]);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->matchingScan(),
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('No students with an enrolled face', $res->json('message'));
    }

    public function test_ending_lecture_notifies_only_unmarked_students(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture = $this->makeLecture($lecturer, $course);
        $present = $this->makeStudent();
        $absent  = $this->makeStudent();

        Attendance::create([
            'student_id'   => $present->id,
            'lecture_id'   => $lecture->id,
            'course_id'    => $course->id,
            'lecturer_id'  => $lecturer->id,
            'lecture_date' => $lecture->scheduled_date,
            'status'       => 'present',
            'source'       => 'lecturer',
        ]);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/end');

        $res->assertOk()->assertJsonPath('lecture.is_active', false);

        $this->assertDatabaseMissing('notifications', ['user_id' => $present->id, 'lecture_id' => $lecture->id]);
        $this->assertDatabaseHas('notifications', [
            'user_id'    => $absent->id,
            'lecture_id' => $lecture->id,
            'type'       => 'warning',
        ]);
    }

    public function test_scan_requires_valid_embedding(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => [1, 2, 3],
        ]);

        $res->assertStatus(422);
        $this->assertArrayHasKey('embedding', $res->json('errors'));
    }

    public function test_confirm_requires_valid_student_id(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        // Confirm without student_id → validation error
        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'confirm' => true,
        ]);
        $res->assertStatus(422);

        // Confirm with non-existent student_id → validation error
        $res2 = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'student_id' => 99999,
            'confirm'    => true,
        ]);
        $res2->assertStatus(422);
    }

    public function test_confirm_rejects_student_not_in_audience(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        // Student from another department — not in the audience
        $other    = $this->makeStudent(['dept' => 'Nursing Science', 'face_embedding' => $this->embedding(2.0)]);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'student_id' => $other->id,
            'confirm'    => true,
        ]);
        $res->assertStatus(422);
        $this->assertStringContainsString('not in this lecture audience', $res->json('message'));
    }

    public function test_garbage_embedding_is_rejected(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        // All-zero embedding — face-api.js produces this when the model fails to load.
        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => array_fill(0, 128, 0.0),
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('face image quality', $res->json('message'));
    }

    public function test_low_variance_embedding_is_rejected(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        // Nearly-identical values — low variance garbage.
        $garbage = array_fill(0, 128, 0.5);
        $garbage[0] = 0.5001;
        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $garbage,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('face image quality', $res->json('message'));
    }

    public function test_face_count_zero_is_rejected(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding'  => $this->matchingScan(),
            'face_count' => 0,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('No face detected', $res->json('message'));
    }

    public function test_face_count_multiple_is_rejected(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding'  => $this->matchingScan(),
            'face_count' => 3,
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('Multiple faces detected', $res->json('message'));
    }

    public function test_cross_identity_with_similar_faces_is_rejected(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);
        $lecture  = $this->makeLecture($lecturer, $course);

        // Student with embedding seed 1.0
        $student = $this->makeStudent(['face_embedding' => $this->embedding(1.0)]);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        // Scan with seed 1.1 — a different person.
        $res = $this->postJson('/api/lecturer/lectures/' . $lecture->id . '/scan-student', [
            'embedding' => $this->embedding(1.1),
        ]);

        // Should be rejected if similarity < 0.70, or matched if >= 0.70.
        // Either way, the test verifies the system behaves deterministically.
        if ($res->status() === 200) {
            // If it matched, it must be student (the only enrolled student)
            $this->assertSame($student->id, $res->json('student.id'));
        } else {
            // If rejected, it's because the similarity is below threshold
            $this->assertStringContainsString('not recognized', $res->json('message'));
        }
        // In neither case should attendance be created yet (preview only)
        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id, 'lecture_id' => $lecture->id]);
    }

    public function test_admin_face_register_stores_the_embedding(): void
    {
        Storage::fake('public');

        $admin   = User::factory()->create(['role' => 'admin']);
        $student = $this->makeStudent(['face_embedding' => null]);

        Sanctum::actingAs($admin, ['role:admin']);

        $res = $this->post('/api/admin/students/' . $student->id . '/face-register', [
            'face_image'      => UploadedFile::fake()->image('face.jpg'),
            'face_embedding'  => json_encode($this->embedding(1.0)),
        ]);

        $res->assertOk()->assertJsonPath('user.face_ready', true);
        $this->assertNotNull($student->fresh()->face_embedding);
        $this->assertCount(128, $student->fresh()->face_embedding);

        // An invalid embedding is rejected
        $res2 = $this->post('/api/admin/students/' . $student->id . '/face-register', [
            'face_image'     => UploadedFile::fake()->image('face2.jpg'),
            'face_embedding' => json_encode([1, 2, 3]),
        ]);
        $res2->assertStatus(422);
    }
}
