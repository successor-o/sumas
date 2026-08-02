<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login(): void
    {
        User::factory()->admin()->create();

        $response = $this->postJson('/api/auth/admin-login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'admin']);
    }

    public function test_student_cannot_access_admin_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['role:student']);

        $response = $this->getJson('/api/admin/stats');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_stats(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create(), ['role:admin']);

        User::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/stats');

        $response->assertOk()
                 ->assertJsonPath('total', 3)
                 ->assertJsonStructure(['pending', 'approved', 'rejected']);
    }

    public function test_admin_can_create_a_lecturer_with_department(): void
    {
        $dept = Department::create(['name' => 'Department of Medicine', 'code' => 'MED']);
        Sanctum::actingAs(User::factory()->admin()->create(), ['role:admin']);

        $response = $this->postJson('/api/admin/lecturers', [
            'name'          => 'Dr. Yemi Ade',
            'email'         => 'y.ade@sumas.edu.ng',
            'password'      => 'secret123',
            'phone'         => '+2348012345678',
            'department_id' => $dept->id,
        ]);

        // `department` is a NOT NULL column — the string must be populated from
        // the chosen department so department-scoped queries keep working.
        $response->assertCreated()
                 ->assertJsonPath('lecturer.department', 'Department of Medicine')
                 ->assertJsonPath('lecturer.department_id', $dept->id);
    }
}
