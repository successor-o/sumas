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
 * Smart attendance — face-scan check-in flow:
 *
 *  - Creating a lecture generates a QR link token + optional attendance toggle
 *    and notifies the lecture audience.
 *  - Students check in by POSTing a live FaceNet embedding (computed in the
 *    browser) + their device id; the server compares it against the embedding
 *    captured at enrollment. One scan per student, one per device, nothing
 *    after the lecture ends.
 *  - Lecturers can still record attendance manually per lecture.
 *  - The student portal reflects marked attendance.
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
            // Students are face-enrolled by default — override with
            // ['face_embedding' => null] to simulate a not-yet-enrolled student.
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
        $this->assertNotNull($res->json('lecture.token'));
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

    public function test_student_attendance_records_carry_the_lecture_marks(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course, ['attendance_score' => 2]);
        $student  = $this->makeStudent();

        Sanctum::actingAs($student, ['role:student']);
        $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-marks-1',
        ])->assertCreated();

        $res = $this->getJson('/api/student/attendance');
        $res->assertOk()
            ->assertJsonCount(1, 'attendances')
            ->assertJsonPath('attendances.0.attendance_score', 2);
    }

    public function test_student_can_scan_to_mark_attendance(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($student, ['role:student']);

        $res = $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-abc-123',
        ]);

        $res->assertCreated()
            ->assertJsonPath('attendance.status', 'present')
            ->assertJsonPath('attendance.source', 'face');

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'lecture_id' => $lecture->id,
            'status'     => 'present',
            'source'     => 'face',
            'device_id'  => 'device-abc-123',
        ]);
    }

    public function test_student_cannot_scan_twice_for_the_same_lecture(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($student, ['role:student']);

        $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-1',
        ])->assertCreated();

        $res = $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-2', // different device, same student
        ]);

        $res->assertStatus(422);
        $this->assertSame('You have already marked your attendance for this lecture.', $res->json('message'));
    }

    public function test_one_device_cannot_be_used_for_multiple_students(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);
        $student1 = $this->makeStudent();
        $student2 = $this->makeStudent();

        Sanctum::actingAs($student1, ['role:student']);
        $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'shared-phone',
        ])->assertCreated();

        // Same phone, second student → locked out.
        Sanctum::actingAs($student2, ['role:student']);
        $res = $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'shared-phone',
        ]);

        $res->assertStatus(422);
        $this->assertSame('This device has already been used to mark attendance for this lecture.', $res->json('message'));
        $this->assertDatabaseMissing('attendances', ['student_id' => $student2->id, 'lecture_id' => $lecture->id]);
    }

    public function test_scan_is_rejected_after_lecture_ends(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course, ['is_active' => false]);
        $student  = $this->makeStudent();

        Sanctum::actingAs($student, ['role:student']);

        $res = $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-x',
        ]);

        $res->assertStatus(422);
        $this->assertSame('This lecture has ended. Attendance is closed.', $res->json('message'));
        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id, 'lecture_id' => $lecture->id]);
    }

    public function test_scan_is_rejected_for_unknown_token(): void
    {
        $student = $this->makeStudent();
        Sanctum::actingAs($student, ['role:student']);

        $this->postJson('/api/student/attend', [
            'token'     => 'does-not-exist',
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-x',
        ])->assertStatus(404);
    }

    public function test_student_from_another_department_cannot_scan(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);
        $other    = $this->makeStudent(['dept' => 'Nursing Science']);

        Sanctum::actingAs($other, ['role:student']);

        $res = $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-y',
        ]);

        $res->assertStatus(403);
        $this->assertSame('You are not registered for this lecture.', $res->json('message'));
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
            'source'       => 'face',
        ]);

        Sanctum::actingAs($student, ['role:student']);

        $res = $this->getJson('/api/student/lectures');

        $res->assertOk()
            ->assertJsonCount(1, 'lectures')
            ->assertJsonPath('lectures.0.attended', 'present')
            // the QR token must never leak to the student payload
            ->assertJsonMissingPath('lectures.0.token');
    }

    public function test_public_lecture_info_is_a_safe_subset(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);

        $res = $this->getJson('/api/attend/' . $lecture->token);

        $res->assertOk()
            ->assertJsonPath('lecture.title', $lecture->title)
            ->assertJsonPath('lecture.is_active', true)
            ->assertJsonMissingPath('lecture.token');
    }

    public function test_token_rotates_after_interval_and_old_token_stays_valid(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        // Token is long past its rotation interval (5 minutes ago).
        $lecture = $this->makeLecture($lecturer, $course, [
            'token'            => 'OLD-TOKEN-0000000000',
            'previous_token'   => null,
            'token_rotated_at' => now()->subMinutes(5),
        ]);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        // Live poll triggers the lazy rotation.
        $live = $this->getJson('/api/lecturer/lectures/' . $lecture->id . '/live');
        $live->assertOk();
        $newToken = $live->json('token');

        $this->assertNotSame('OLD-TOKEN-0000000000', $newToken);
        $this->assertSame('OLD-TOKEN-0000000000', $live->json('previous_token'));

        // The rotated-out token still works (one rotation grace window).
        $student = $this->makeStudent();
        Sanctum::actingAs($student, ['role:student']);
        $this->postJson('/api/student/attend', [
            'token'     => 'OLD-TOKEN-0000000000',
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-grace',
        ])->assertCreated();

        // And the fresh token works too.
        $student2 = $this->makeStudent();
        Sanctum::actingAs($student2, ['role:student']);
        $this->postJson('/api/student/attend', [
            'token'     => $newToken,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-fresh',
        ])->assertCreated();

        // A bogus token is rejected.
        $student3 = $this->makeStudent();
        Sanctum::actingAs($student3, ['role:student']);
        $this->postJson('/api/student/attend', [
            'token'     => 'MADE-UP-TOKEN-0000',
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-bogus',
        ])->assertStatus(404);
    }

    public function test_fresh_lecture_does_not_rotate_on_first_live_poll(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecturer->courses()->attach($course->id);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $create = $this->postJson('/api/lecturer/lectures', [
            'course_id'      => $course->id,
            'title'          => 'Fresh Lecture',
            'content'        => 'Should not rotate immediately.',
            'scheduled_date' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertCreated();

        $token = $create->json('lecture.token');
        $id    = $create->json('lecture.id');

        // The first live poll must keep the same token (rotation starts only
        // after the interval elapses).
        $live = $this->getJson('/api/lecturer/lectures/' . $id . '/live');
        $live->assertOk()->assertJsonPath('token', $token);
        $this->assertStringContainsString('/attend/' . $token, $live->json('qr_url'));
    }

    public function test_6_digit_code_is_no_longer_accepted(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($student, ['role:student']);

        $res = $this->postJson('/api/student/attend', [
            'code'      => '123456',
            'device_id' => 'device-code-1',
        ]);

        $res->assertStatus(422);
        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id, 'lecture_id' => $lecture->id]);
    }

    public function test_student_without_enrolled_face_cannot_check_in(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent(['face_embedding' => null]);

        Sanctum::actingAs($student, ['role:student']);

        $res = $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-no-enroll',
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('not enrolled', $res->json('message'));
        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id, 'lecture_id' => $lecture->id]);
    }

    public function test_face_mismatch_is_rejected(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent(); // enrolled with embedding(1.0)

        Sanctum::actingAs($student, ['role:student']);

        $res = $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->wrongScan(),
            'device_id' => 'device-wrong-face',
        ]);

        $res->assertStatus(422);
        $this->assertStringContainsString('did not match', $res->json('message'));
        $this->assertDatabaseMissing('attendances', ['student_id' => $student->id, 'lecture_id' => $lecture->id]);
    }

    public function test_student_can_check_in_from_dashboard_by_lecture_id(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($student, ['role:student']);

        $res = $this->postJson('/api/student/lectures/' . $lecture->id . '/face-checkin', [
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-dash-1',
        ]);

        $res->assertCreated()
            ->assertJsonPath('attendance.source', 'face')
            ->assertJsonPath('attendance.lecture_id', $lecture->id);

        // A mismatching face is rejected on this endpoint too.
        $student2 = $this->makeStudent();
        Sanctum::actingAs($student2, ['role:student']);
        $res = $this->postJson('/api/student/lectures/' . $lecture->id . '/face-checkin', [
            'embedding' => $this->wrongScan(),
            'device_id' => 'device-dash-2',
        ]);
        $res->assertStatus(422);
    }

    public function test_face_checkin_requires_a_valid_embedding(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course);
        $student  = $this->makeStudent();

        Sanctum::actingAs($student, ['role:student']);

        // Wrong-sized embedding → validation error.
        $res = $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => [1, 2, 3],
            'device_id' => 'device-bad-embed',
        ]);
        $res->assertStatus(422);
        $this->assertArrayHasKey('embedding', $res->json('errors'));
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

        // An invalid embedding is rejected.
        $res2 = $this->post('/api/admin/students/' . $student->id . '/face-register', [
            'face_image'     => UploadedFile::fake()->image('face2.jpg'),
            'face_embedding' => json_encode([1, 2, 3]),
        ]);
        $res2->assertStatus(422);
    }

    public function test_gps_geofence_blocks_students_outside_the_radius(): void
    {
        $dept     = $this->makeDept();
        $course   = $this->makeCourse($dept);
        $lecturer = $this->makeLecturer();
        $lecture  = $this->makeLecture($lecturer, $course, [
            'gps_required' => true,
            'latitude'     => 6.4500000,  // Enugu
            'longitude'    => 7.5000000,
        ]);

        // Inside the default 200m radius (~60m away).
        $near = $this->makeStudent();
        Sanctum::actingAs($near, ['role:student']);
        $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-near',
            'latitude'  => 6.4505000,
            'longitude' => 7.5005000,
        ])->assertCreated();

        // Far away (Lagos ~400km) → rejected.
        $far = $this->makeStudent();
        Sanctum::actingAs($far, ['role:student']);
        $res = $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-far',
            'latitude'  => 6.5244000,
            'longitude' => 3.3792000,
        ]);
        $res->assertStatus(422);
        $this->assertStringContainsString('outside the lecture location', $res->json('message'));

        // No coordinates → rejected with a clear message.
        $noloc = $this->makeStudent();
        Sanctum::actingAs($noloc, ['role:student']);
        $res = $this->postJson('/api/student/attend', [
            'token'     => $lecture->token,
            'embedding' => $this->matchingScan(),
            'device_id' => 'device-noloc',
        ]);
        $res->assertStatus(422);
        $this->assertStringContainsString('Location access is required', $res->json('message'));
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

        // present marked attendance; absent did not
        Attendance::create([
            'student_id'   => $present->id,
            'lecture_id'   => $lecture->id,
            'course_id'    => $course->id,
            'lecturer_id'  => $lecturer->id,
            'lecture_date' => $lecture->scheduled_date,
            'status'       => 'present',
            'source'       => 'face',
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
}
