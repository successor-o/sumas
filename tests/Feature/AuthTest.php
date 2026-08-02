<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeDepartment(): Department
    {
        return Department::create([
            'name' => 'Computer Science',
            'code' => 'CS',
        ]);
    }

    public function test_student_can_register_with_documents_and_is_pending(): void
    {
        $dept = $this->makeDepartment();

        $response = $this->post('/api/auth/register', [
            'name'          => 'Chukwuemeka Obi',
            'matric'        => 'SUMAS/CS/2023/001',
            'email'         => 'chuka@sumas.edu.ng',
            'phone'         => '+2348012345678',
            'password'      => 'secret123',
            'department_id' => $dept->id,
            'level'         => '300 Level',
            'documents'     => [
                'school-id' => UploadedFile::fake()->image('school-id.jpg'),
                'admission' => UploadedFile::fake()->image('admission.jpg'),
                'clearance' => UploadedFile::fake()->image('clearance.jpg'),
                'pp-1'      => UploadedFile::fake()->image('pp-1.jpg'),
            ],
        ], ['Accept' => 'application/json']);

        $response->assertStatus(201)
                 ->assertJsonStructure(['user' => ['matric', 'status', 'docs_count']])
                 ->assertJsonPath('user.status', 'Pending')
                 ->assertJsonPath('user.docs_count', 4);

        // No session token is issued — the student cannot sign in until approved.
        $this->assertArrayNotHasKey('token', $response->json());

        $user = User::where('matric', 'SUMAS/CS/2023/001')->first();
        $this->assertNotNull($user);
        $this->assertSame('Pending', $user->status);
        $this->assertDatabaseCount('documents', 4);
    }

    public function test_approved_student_can_login(): void
    {
        User::factory()->create([
            'matric'   => 'SUMAS/CS/2023/001',
            'email'    => 'chuka@sumas.edu.ng',
            'password' => bcrypt('secret123'),
            'status'   => 'Approved',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => 'secret123',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_pending_student_cannot_login(): void
    {
        User::factory()->create([
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => bcrypt('secret123'),
            'status'   => 'Pending',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)
                 ->assertJsonPath('message', 'Your registration is still under review. Please check the register page for status updates.');
    }

    public function test_rejected_student_cannot_login(): void
    {
        User::factory()->create([
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => bcrypt('secret123'),
            'status'   => 'Rejected',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => 'secret123',
        ]);

        $response->assertStatus(403)
                 ->assertJsonPath('message', 'Your registration was not approved. Please contact the SUMAS Registrar.');
    }

    public function test_pending_student_cannot_access_student_api(): void
    {
        $user = User::factory()->create(['status' => 'Pending']);
        Sanctum::actingAs($user, ['role:student']);

        $response = $this->getJson('/api/student/dashboard');

        $response->assertStatus(403);
    }

    public function test_student_login_starts_a_web_session(): void
    {
        $user = User::factory()->create([
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => bcrypt('secret123'),
            'status'   => 'Approved',
        ]);

        $this->postJson('/api/auth/login', [
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => 'secret123',
        ])->assertOk();

        // The login must have started a real web session so server-side pages
        // (and the auth.redirect middleware) recognise the user on navigation.
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_login_starts_a_web_session(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/auth/admin-login', [
            'username' => 'admin',
            'password' => 'password',
        ])->assertOk();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_lecturer_login_starts_a_lecturer_session(): void
    {
        $lecturer = Lecturer::create([
            'name'       => 'Dr. Ada Obi',
            'email'      => 'ada.obi@sumas.edu.ng',
            'password'   => bcrypt('secret123'),
            'department' => 'Computer Science',
            'is_active'  => true,
        ]);

        $this->postJson('/api/auth/lecturer-login', [
            'email'    => 'ada.obi@sumas.edu.ng',
            'password' => 'secret123',
        ])->assertOk();

        $this->assertAuthenticatedAs($lecturer, 'lecturer');
    }

    public function test_logout_destroys_the_web_session(): void
    {
        $user = User::factory()->create([
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => bcrypt('secret123'),
            'status'   => 'Approved',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => 'secret123',
        ]);
        $login->assertOk();
        $this->assertAuthenticated();

        $this->withToken($login->json('token'))
             ->postJson('/api/auth/logout')
             ->assertOk();

        $this->assertGuest();
    }

    public function test_session_status_endpoint_reflects_the_backend_session(): void
    {
        $this->getJson('/api/session/status')
             ->assertOk()
             ->assertJsonPath('authenticated', false);

        User::factory()->create([
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => bcrypt('secret123'),
            'status'   => 'Approved',
        ]);

        $this->postJson('/api/auth/login', [
            'matric'   => 'SUMAS/CS/2023/001',
            'password' => 'secret123',
        ])->assertOk();

        $this->getJson('/api/session/status')
             ->assertOk()
             ->assertJson(['authenticated' => true, 'role' => 'student']);
    }

    public function test_public_status_check(): void
    {
        $user = User::factory()->create([
            'matric' => 'SUMAS/CS/2023/001',
            'status' => 'Pending',
        ]);

        $response = $this->getJson('/api/student/status?matric=' . urlencode($user->matric));

        $response->assertOk()->assertJsonPath('user.status', 'Pending');
    }

    public function test_auth_status_check_endpoint_returns_status(): void
    {
        $user = User::factory()->create([
            'matric' => 'SUMAS/CS/2023/001',
            'status' => 'Approved',
        ]);

        $response = $this->getJson('/api/auth/check-status?matric=' . urlencode($user->matric));

        $response->assertOk()->assertJsonPath('status', 'Approved');
    }

    public function test_status_check_returns_404_for_unknown_matric(): void
    {
        $response = $this->getJson('/api/auth/check-status?matric=' . urlencode('SUMAS/CS/2023/999'));

        $response->assertStatus(404);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'matric'   => 'SUMAS/CS/2023/999',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401);
    }

    public function test_student_can_browse_courses_by_department_and_faculty(): void
    {
        $faculty = Faculty::create(['name' => 'Faculty of Computing & Applied Sciences', 'code' => 'FCS']);
        $dept    = Department::create(['name' => 'Computer Science', 'code' => 'CS', 'faculty_id' => $faculty->id]);

        Course::create([
            'code'          => 'CS101',
            'name'          => 'Introduction to Computer Science',
            'department'    => 'Computer Science',
            'department_id' => $dept->id,
            'credit_units'  => 3,
            'level'         => '100 Level',
            'is_active'     => true,
        ]);

        $user = User::factory()->create(['status' => 'Approved']);
        Sanctum::actingAs($user, ['role:student']);

        // Browse by department
        $byDept = $this->getJson('/api/student/courses?department_id=' . $dept->id);
        $byDept->assertOk()
               ->assertJsonCount(1, 'courses')
               ->assertJsonPath('courses.0.code', 'CS101');

        // Browse by faculty
        $byFaculty = $this->getJson('/api/student/courses?faculty_id=' . $faculty->id);
        $byFaculty->assertOk()->assertJsonCount(1, 'courses');

        // No filters → enrolled courses (empty for this student)
        $enrolled = $this->getJson('/api/student/courses');
        $enrolled->assertOk()->assertJsonCount(0, 'courses');
    }
}
