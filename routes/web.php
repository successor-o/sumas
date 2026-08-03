<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| SUMAS SmartAttend — Blade frontend served by Laravel.
| The frontend pages (converted from frontend/*.html) are rendered here.
|
*/

Route::view('/', 'home')->name('home');

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');

/*
| QR smart-attendance check-in page — public. Students open the URL encoded in
| the lecturer's QR code. The page shows a safe subset of the lecture info and
| lets an approved student log in and mark their attendance in one tap.
| (The /api/attend/{token} endpoint behind it never exposes the lecture token.)
*/
Route::get('/attend/{token}', function (string $token) {
    return view('attend', ['token' => $token]);
});

// Manual 6-digit code entry (no QR scan) — same check-in page without a token.
Route::get('/attend', function () {
    return view('attend', ['token' => null]);
});

/*
| These dashboards are protected by the auth.redirect middleware. Because login
| now starts a real backend session, a visitor without an active session — or
| whose session was deleted/expired — is redirected to their role's login page
| (admin/* → admin login, lecturer/* → lecturer login, else → student login).
| Authenticated users pass through; the client still guards the API data.
*/
Route::view('/dashboard', 'student.dashboard')->name('student.dashboard')->middleware('auth.redirect');

Route::view('/admin/login', 'admin.login')->name('admin.login');
Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard')->middleware('auth.redirect');

Route::view('/lecturer/login', 'lecturer.login')->name('lecturer.login');
Route::view('/lecturer/dashboard', 'lecturer.dashboard')->name('lecturer.dashboard')->middleware('auth.redirect');
