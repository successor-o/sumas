<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression tests for the lecturer portal:
 *
 *  1. Login no longer bounces the lecturer back out. The Sanctum guard resolves
 *     the `web` session BEFORE the bearer token, so a leftover student/admin
 *     session in the same cookie made every lecturer API call resolve as that
 *     user → 403 → the client cleared the session and sent the lecturer back to
 *     the login page. Logins now keep the role sessions mutually exclusive.
 *
 *  2. The Students page loads only APPROVED students from the lecturer's own
 *     department (department_id is the source of truth, not the legacy
 *     `department` string column which is empty for admin-created lecturers).
 */
class LecturerSessionTest extends TestCase
{
    use RefreshDatabase;

    private function makeLecturer(array $overrides = []): Lecturer
    {
        return Lecturer::create(array_merge([
            'name'       => 'Dr. Ada Obi',
            'email'      => 'ada.obi@sumas.edu.ng',
            'password'   => bcrypt('secret123'),
            'department' => 'Department of Computer Science',
            'is_active'  => true,
        ], $overrides));
    }

    public function test_lecturer_api_works_even_when_a_student_web_session_exists(): void
    {
        // The student signs in first, leaving a web-guard session behind.
        User::factory()->create([
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => bcrypt('secret123'),
            'status'   => 'Approved',
        ]);
        $this->postJson('/api/auth/login', [
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => 'secret123',
        ])->assertOk();

        $this->makeLecturer();

        $login = $this->postJson('/api/auth/lecturer-login', [
            'email'    => 'ada.obi@sumas.edu.ng',
            'password' => 'secret123',
        ]);
        $login->assertOk();
        $token = $login->json('token');

        // The dashboard runs this exact call on load (SessionStore.verify).
        // Before the fix this returned 403 → the lecturer was logged back out.
        $this->withToken($token)->getJson('/api/lecturer/auth/me')->assertOk();
    }

    public function test_lecturer_login_clears_a_previous_student_web_session(): void
    {
        User::factory()->create([
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => bcrypt('secret123'),
            'status'   => 'Approved',
        ]);
        $this->postJson('/api/auth/login', [
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => 'secret123',
        ])->assertOk();

        $this->makeLecturer();
        $this->postJson('/api/auth/lecturer-login', [
            'email'    => 'ada.obi@sumas.edu.ng',
            'password' => 'secret123',
        ])->assertOk();

        // The student's web session must have been dropped — only the lecturer
        // session may remain, so /api/session/status reports the right role.
        $this->getJson('/api/session/status')
             ->assertOk()
             ->assertJson(['authenticated' => true, 'role' => 'lecturer']);
    }

    public function test_lecturer_sees_only_approved_students_from_their_department(): void
    {
        $med   = Department::create(['name' => 'Department of Medicine', 'code' => 'MED']);
        $surg  = Department::create(['name' => 'Department of Surgery', 'code' => 'SURG']);

        // Lecturer created like the admin panel does it: department_id set, the
        // legacy `department` string empty.
        $lecturer = $this->makeLecturer([
            'email'         => 'dr.x@sumas.edu.ng',
            'department'    => '',
            'department_id' => $med->id,
        ]);

        User::factory()->create(['status' => 'Approved', 'dept' => 'Department of Medicine', 'matric' => 'SUMAS/CS/2023/010']);
        User::factory()->create(['status' => 'Approved', 'dept' => 'Department of Surgery', 'matric' => 'SUMAS/CS/2023/011']);
        User::factory()->create(['status' => 'Pending', 'dept' => 'Department of Medicine', 'matric' => 'SUMAS/CS/2023/012']);

        Sanctum::actingAs($lecturer, ['role:lecturer']);

        $students = $this->getJson('/api/lecturer/students');
        $students->assertOk()
                 ->assertJsonCount(1, 'students')
                 ->assertJsonPath('students.0.matric', 'SUMAS/CS/2023/010');

        // The dashboard student count must match the same department scope.
        $this->getJson('/api/lecturer/dashboard')
             ->assertOk()
             ->assertJsonPath('students_count', 1);
    }

    public function test_clean_lecturer_login_reaches_the_dashboard_api(): void
    {
        $this->makeLecturer();

        $login = $this->postJson('/api/auth/lecturer-login', [
            'email'    => 'ada.obi@sumas.edu.ng',
            'password' => 'secret123',
        ]);
        $login->assertOk();
        $token = $login->json('token');

        $this->withToken($token)->getJson('/api/lecturer/auth/me')->assertOk();
        $this->withToken($token)->getJson('/api/lecturer/dashboard')->assertOk();
    }
}
