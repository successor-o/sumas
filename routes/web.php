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
