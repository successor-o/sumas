<?php
/**
 * SUMAS SmartAttend — API Routes
 * Laravel 12 | Sanctum Auth
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
|──────────────────────────────────────────────────────────────
|  PUBLIC ROUTES (no auth required)
|──────────────────────────────────────────────────────────────
*/

// Student auth
Route::prefix('auth')->group(function () {
    Route::post('/register',       [AuthController::class, 'register']);
    Route::post('/login',          [AuthController::class, 'studentLogin']);
    Route::post('/admin-login',    [AuthController::class, 'adminLogin']);
    Route::post('/lecturer-login', [AuthController::class, 'lecturerLogin']);
    Route::get('/check-status',    [AuthController::class, 'checkStatus']);
});

// Public status check (matric contains slashes, so it is passed as a query param)
Route::get('/student/status', [StudentController::class, 'checkStatus']);

// Public departments list
Route::get('/departments', function () {
    return response()->json([
        'departments' => \App\Models\Department::where('is_active', true)->get(['id', 'name', 'code', 'faculty_id'])
    ]);
});

// Public faculties list
Route::get('/faculties', function () {
    return response()->json([
        'faculties' => \App\Models\Faculty::where('is_active', true)->get(['id', 'name', 'code'])
    ]);
});

// Public session check — lets the login pages confirm the backend session is
// still active before bouncing a logged-in visitor to their dashboard.
Route::get('/session/status', [AuthController::class, 'sessionStatus']);

/*
|──────────────────────────────────────────────────────────────
|  AUTHENTICATED STUDENT ROUTES
|──────────────────────────────────────────────────────────────
*/
Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::post('/auth/logout',       [AuthController::class, 'logout']);
    Route::get('/auth/me',            [AuthController::class, 'me']);
    Route::get('/student/dashboard',  [StudentController::class, 'dashboard']);
    Route::put('/student/profile',    [StudentController::class, 'updateProfile']);

    // Document uploads (real files)
    Route::get('/student/documents',              [StudentController::class, 'documents']);
    Route::post('/student/documents',             [StudentController::class, 'uploadDocument']);
    Route::delete('/student/documents/{id}',      [StudentController::class, 'deleteDocument']);

    // Course and attendance
    Route::get('/student/courses',     [StudentController::class, 'courses']);
    Route::get('/student/attendance',  [StudentController::class, 'attendance']);
    Route::get('/student/lectures',    [StudentController::class, 'lectures']);
    Route::get('/student/notifications', [StudentController::class, 'notifications']);
    Route::put('/student/notifications/{id}/read', [StudentController::class, 'markNotificationRead']);
});

/*
|──────────────────────────────────────────────────────────────
|  AUTHENTICATED ADMIN ROUTES
|──────────────────────────────────────────────────────────────
*/
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    Route::get('/stats',                          [AdminController::class, 'stats']);
    Route::get('/students',                       [AdminController::class, 'students']);
    Route::post('/students',                      [AdminController::class, 'createStudent']);
    Route::get('/students/{id}',                  [AdminController::class, 'getStudent']);
    Route::put('/students/{id}',                  [AdminController::class, 'updateStudent']);
    Route::put('/students/{id}/status',           [AdminController::class, 'updateStatus']);
    Route::post('/students/{id}/face-register',   [AdminController::class, 'faceRegister']);
    Route::delete('/students/{id}',               [AdminController::class, 'deleteStudent']);
    Route::post('/password',                      [AdminController::class, 'changePassword']);
    Route::get('/export',                         [AdminController::class, 'exportCsv']);

    // Course management
    Route::get('/courses',                        [AdminController::class, 'courses']);
    Route::post('/courses',                       [AdminController::class, 'createCourse']);
    Route::put('/courses/{id}',                   [AdminController::class, 'updateCourse']);
    Route::delete('/courses/{id}',                [AdminController::class, 'deleteCourse']);
    Route::post('/courses/{courseId}/assign-lecturer', [AdminController::class, 'assignLecturer']);
    Route::delete('/courses/{courseId}/lecturers/{lecturerId}', [AdminController::class, 'removeLecturer']);

    // Lecturer management
    Route::get('/lecturers',                      [AdminController::class, 'lecturers']);
    Route::post('/lecturers',                     [AdminController::class, 'createLecturer']);
    Route::put('/lecturers/{id}',                [AdminController::class, 'updateLecturer']);
    Route::delete('/lecturers/{id}',             [AdminController::class, 'deleteLecturer']);
    Route::get('/departments',                    [AdminController::class, 'departments']);

    // Faculty management
    Route::get('/faculties',                      [AdminController::class, 'faculties']);
    Route::post('/faculties',                     [AdminController::class, 'createFaculty']);
    Route::put('/faculties/{id}',                [AdminController::class, 'updateFaculty']);
    Route::delete('/faculties/{id}',             [AdminController::class, 'deleteFaculty']);

    // Department management
    Route::get('/departments/all',                [AdminController::class, 'allDepartments']);
    Route::post('/departments',                   [AdminController::class, 'createDepartment']);
    Route::put('/departments/{id}',              [AdminController::class, 'updateDepartment']);
    Route::delete('/departments/{id}',           [AdminController::class, 'deleteDepartment']);

    // Academic level management
    Route::get('/academic-levels',                [AdminController::class, 'academicLevels']);
    Route::post('/academic-levels',               [AdminController::class, 'createAcademicLevel']);
    Route::put('/academic-levels/{id}',          [AdminController::class, 'updateAcademicLevel']);
    Route::delete('/academic-levels/{id}',       [AdminController::class, 'deleteAcademicLevel']);
});

/*
|──────────────────────────────────────────────────────────────
|  AUTHENTICATED LECTURER ROUTES
|──────────────────────────────────────────────────────────────
*/
Route::middleware(['auth:sanctum', 'role:lecturer'])->prefix('lecturer')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    Route::get('/dashboard',    [LecturerController::class, 'dashboard']);
    Route::get('/students',     [LecturerController::class, 'students']);
    Route::get('/courses',      [LecturerController::class, 'courses']);
    Route::post('/lectures',    [LecturerController::class, 'createLecture']);
    Route::get('/lectures',     [LecturerController::class, 'lectures']);
    Route::get('/lectures/{id}/live', [LecturerController::class, 'liveCode']);
    Route::post('/lectures/{id}/end', [LecturerController::class, 'endLecture']);
    Route::post('/lectures/{id}/scan-student', [LecturerController::class, 'scanStudent']);
    Route::post('/attendance',  [LecturerController::class, 'recordAttendance']);
    Route::get('/attendance/{lectureId}', [LecturerController::class, 'getAttendance']);
});
