<?php

namespace App\Http\Middleware;

use App\Models\Lecturer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckRole middleware
 * Usage in routes:  middleware(['auth:sanctum', 'role:admin'])
 *
 * Checks the authenticated user's 'role' column or model type.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Handle lecturer authentication (Lecturer model)
        if ($user instanceof Lecturer) {
            if ($role !== 'lecturer') {
                return response()->json([
                    'message' => 'Access denied. Lecturers cannot access this resource.',
                ], 403);
            }
            return $next($request);
        }

        // Handle User model authentication (students and admins)
        if ($user->role !== $role) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to perform this action.',
            ], 403);
        }

        // Students cannot access the portal until their registration has been
        // approved by the administration.
        if ($role === 'student' && $user->status !== 'Approved') {
            $message = $user->status === 'Rejected'
                ? 'Your registration was not approved. Please contact the SUMAS Registrar.'
                : 'Your registration is still under review. Please check the register page for status updates.';

            return response()->json(['message' => $message], 403);
        }

        return $next($request);
    }
}
