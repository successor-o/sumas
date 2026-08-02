<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Guards the redirect-loop fix and the session-based protection behaviour.
 *
 * Login starts a real backend session, and the dashboard routes are protected
 * by the auth.redirect middleware: a visitor with no active session — or with
 * a deleted/expired session — is redirected to their role's login page
 * (admin/* → /admin/login, lecturer/* → /lecturer/login, else → /login).
 * The login pages verify the backend session before bouncing back, so the old
 * infinite redirect loop cannot reappear.
 */
class RedirectMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_visitors_are_redirected_to_their_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->get('/lecturer/dashboard')->assertRedirect('/lecturer/login');
    }

    public function test_login_and_register_routes_are_served(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/admin/login')->assertOk();
        $this->get('/lecturer/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_dashboard_loads_after_student_login(): void
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

        $this->get('/dashboard')->assertOk();
    }

    public function test_admin_dashboard_loads_after_admin_login(): void
    {
        User::factory()->admin()->create();

        $this->postJson('/api/auth/admin-login', [
            'username' => 'admin',
            'password' => 'password',
        ])->assertOk();

        $this->get('/admin/dashboard')->assertOk();
    }

    public function test_lecturer_dashboard_loads_after_lecturer_login(): void
    {
        Lecturer::create([
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

        $this->get('/lecturer/dashboard')->assertOk();
    }

    public function test_middleware_redirects_unauthenticated_visitors_to_their_login(): void
    {
        Route::middleware('auth.redirect')->get('/mw/student', fn () => 'ok');
        Route::middleware('auth.redirect')->get('/admin/mw-check', fn () => 'ok');
        Route::middleware('auth.redirect')->get('/lecturer/mw-check', fn () => 'ok');

        $this->get('/mw/student')->assertRedirect('/login');
        $this->get('/admin/mw-check')->assertRedirect('/admin/login');
        $this->get('/lecturer/mw-check')->assertRedirect('/lecturer/login');
    }

    public function test_middleware_lets_session_authenticated_users_through(): void
    {
        Route::middleware('auth.redirect')->get('/mw/session', fn () => 'ok');

        $user = User::factory()->create();

        $this->actingAs($user)->get('/mw/session')->assertOk();
    }

    public function test_middleware_lets_token_authenticated_users_through(): void
    {
        Route::middleware('auth.redirect')->get('/mw/token', fn () => 'ok');

        Sanctum::actingAs(User::factory()->create());

        $this->get('/mw/token')->assertOk();
    }
}
