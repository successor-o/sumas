<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Document;
use App\Models\Faculty;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new student.
     * POST /api/auth/register  (multipart/form-data so documents can be attached)
     *
     * Students are created with status 'Pending'. No session token is issued:
     * a student may only sign in once the administration approves the registration.
     * Any documents uploaded with the form are stored immediately so the admin
     * can review them alongside the pending application.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all() + $request->allFiles(), [
            'name' => 'required|string|min:2|max:120',
            'matric' => 'required|string|unique:users,matric|max:50',
            'email' => 'required|email|unique:users,email|max:120',
            'phone' => 'required|string|min:8|max:20',
            'password' => ['required', 'string', Password::min(6)],
            'department_id' => 'required|exists:departments,id',
            'level' => 'required|string|max:20',
            'gender' => 'nullable|string|max:30',
            'dob' => 'nullable|date',
            'state_of_origin' => 'nullable|string|max:60',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        
        // Get department and faculty info
        $department = Department::find($data['department_id']);
        $faculty = $department?->faculty;

        $user = User::create([
            'name' => $data['name'],
            'matric' => strtoupper(trim($data['matric'])),
            'email' => strtolower(trim($data['email'])),
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'status' => 'Pending',
            'dept' => $department?->name,
            'level' => $data['level'],
            'gender' => $data['gender'] ?? null,
            'dob' => $data['dob'] ?? null,
            'state_of_origin' => $data['state_of_origin'] ?? null,
            'faculty' => $faculty?->name,
            // Face verification is performed by an administrator via webcam
            // after approval — never trusted from the client.
            'verified' => false,
            'docs_count' => 0,
        ]);

        // Store any documents submitted with the registration form (multipart).
        // They are saved against the pending application so the administration
        // can review them immediately — no student session is required.
        $allowedDocs = ['school-id', 'admission', 'clearance', 'nat-id', 'pp-1', 'pp-2', 'pp-3'];
        $docCount = 0;
        $documents = $request->file('documents');
        if (is_array($documents)) {
            foreach ($documents as $type => $file) {
                if (! $file || ! in_array($type, $allowedDocs, true)) {
                    continue;
                }
                $stored = $file->store("documents/{$user->id}", 'public');
                Document::create([
                    'user_id' => $user->id,
                    'doc_type' => $type,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => basename($stored),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
                $docCount++;
            }
        }
        if ($docCount > 0) {
            $user->update(['docs_count' => $docCount]);
        }

        // No token is issued: access is granted only after admin approval,
        // at which point the student signs in with matric + password.
        return response()->json([
            'message' => 'Registration submitted successfully. Your documents have been received. You will be able to sign in once the administration approves your registration.',
            'user' => $this->formatUser($user->fresh()),
        ], 201);
    }

    /**
     * Student login by matric + password.
     * POST /api/auth/login
     */
    public function studentLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'matric' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Matric number and password are required.'], 422);
        }

        $user = User::where('matric', strtoupper(trim($request->matric)))
            ->where('role', 'student')
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid matric number or password.'], 401);
        }

        if ($user->status === 'Rejected') {
            return response()->json(['message' => 'Your registration was not approved. Please contact the SUMAS Registrar.'], 403);
        }

        if ($user->status !== 'Approved') {
            return response()->json(['message' => 'Your registration is still under review. Please check the register page for status updates.'], 403);
        }

        // Revoke old tokens
        $user->tokens()->delete();
        $token = $user->createToken('student-token', ['role:student'])->plainTextToken;

        // Start a real web session so server-side (session-based) pages and the
        // auth.redirect middleware can recognise this user on page navigations.
        // Clear any lingering lecturer session first — role sessions are
        // mutually exclusive so the Sanctum guard never resolves the wrong user.
        Auth::guard('lecturer')->logout();
        Auth::login($user);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Admin login by username + password.
     * POST /api/auth/admin-login
     */
    public function adminLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Username and password are required.'], 422);
        }

        $admin = User::where('username', $request->username)
            ->where('role', 'admin')
            ->first();

        if (! $admin || ! Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Invalid admin credentials.'], 401);
        }

        $admin->tokens()->delete();
        $token = $admin->createToken('admin-token', ['role:admin'])->plainTextToken;

        // Start a real web session so server-side (session-based) pages and the
        // auth.redirect middleware can recognise this user on page navigations.
        // Clear any lingering lecturer session first (see lecturerLogin).
        Auth::guard('lecturer')->logout();
        Auth::login($admin);

        return response()->json([
            'message' => 'Admin login successful.',
            'token' => $token,
            'admin' => ['id' => $admin->id, 'username' => $admin->username, 'name' => $admin->name, 'role' => 'admin'],
        ]);
    }

    /**
     * Lecturer login by email + password.
     * POST /api/auth/lecturer-login
     */
    public function lecturerLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Email and password are required.'], 422);
        }

        $lecturer = Lecturer::where('email', strtolower(trim($request->email)))->first();

        if (! $lecturer || ! Hash::check($request->password, $lecturer->password)) {
            return response()->json(['message' => 'Invalid lecturer credentials.'], 401);
        }

        if (! $lecturer->is_active) {
            return response()->json(['message' => 'Your lecturer account is not active. Please contact administration.'], 403);
        }

        $lecturer->tokens()->delete();
        $token = $lecturer->createToken('lecturer-token', ['role:lecturer'])->plainTextToken;

        // Start a session on the dedicated lecturer guard (lecturers are a
        // different model than students/admins), so server-side pages and the
        // auth.redirect middleware can recognise lecturers on navigations.
        //
        // The Sanctum guard authenticates the `web` session BEFORE the bearer
        // token, so a leftover student/admin web session in the same session
        // cookie would shadow the lecturer's token — every lecturer API call
        // would resolve as that user and return 403, bouncing the lecturer
        // straight back to the login page ("logged out after login"). Clear the
        // web session so the two role sessions never coexist.
        Auth::guard('web')->logout();
        Auth::guard('lecturer')->login($lecturer);

        return response()->json([
            'message' => 'Lecturer login successful.',
            'token' => $token,
            'lecturer' => [
                'id' => $lecturer->id,
                'name' => $lecturer->name,
                'email' => $lecturer->email,
                'department' => $lecturer->department,
                'role' => 'lecturer',
            ],
        ]);
    }

    /**
     * Logout — revoke current token.
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the client's personal access token. When the request carries a
        // valid web session (started at login), Sanctum authenticates it with a
        // TransientToken and skips the bearer token — so fall back to revoking
        // whatever token was sent in the Authorization header.
        $accessToken = $request->user()->currentAccessToken();
        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        } elseif ($bearer = $request->bearerToken()) {
            PersonalAccessToken::findToken($bearer)?->delete();
        }

        // Destroy any web/lecturer session started at login so the server-side
        // auth state matches the client's signed-out state. The sanctum guard's
        // cached user is forgotten too, so a user resolved earlier in this
        // request cannot linger in memory (relevant for long-lived processes
        // and tests).
        Auth::guard('web')->logout();
        Auth::guard('lecturer')->logout();
        Auth::guard('sanctum')->forgetUser();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Report whether an active backend session exists and which role it belongs
     * to. Used by the login pages before bouncing a "logged in" visitor to
     * their dashboard — if the session was deleted or expired, the client can
     * clear its stale token instead of bouncing back and forth.
     * GET /api/session/status
     */
    public function sessionStatus(Request $request): JsonResponse
    {
        if (Auth::guard('lecturer')->check()) {
            return response()->json(['authenticated' => true, 'role' => 'lecturer']);
        }

        if (Auth::check()) {
            return response()->json(['authenticated' => true, 'role' => Auth::user()->role]);
        }

        return response()->json(['authenticated' => false]);
    }

    /**
     * Check registration status by matric number (public endpoint).
     * GET /api/auth/check-status?matric=XXX
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'matric' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Matric number is required.'], 422);
        }

        $user = User::where('matric', strtoupper(trim($request->matric)))
            ->where('role', 'student')
            ->first();

        if (! $user) {
            return response()->json(['message' => 'No registration found with this matric number.'], 404);
        }

        return response()->json([
            'message' => 'Registration status retrieved.',
            'status' => $user->status,
            'verified' => $user->verified,
            'name' => $user->name,
            'dept' => $user->dept,
            'level' => $user->level,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * Get authenticated user info.
     * GET /api/auth/me | /api/admin/auth/me | /api/lecturer/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Lecturers use the Lecturer model (not User), so format them
        // separately — formatUser() is typed for the User model only.
        if ($user instanceof Lecturer) {
            return response()->json(['user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department,
                'role' => 'lecturer',
            ]]);
        }

        return response()->json(['user' => $this->formatUser($user)]);
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
            'docs_count' => $user->docs_count,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
