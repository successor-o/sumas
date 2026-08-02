<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfUnauthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        // This middleware ONLY handles unauthenticated redirection — role-based
        // access control is handled by the client (SessionStore.requireX) and by
        // the API 'role' middleware. A request counts as authenticated when it
        // carries a valid web session, a valid lecturer session, or a valid
        // Sanctum bearer token.
        $authenticated = Auth::check() || Auth::guard('lecturer')->check() || Auth::guard('sanctum')->check();

        if (! $authenticated) {
            $path = $request->path();

            // Don't redirect if already on a login page
            if (in_array($path, ['login', 'admin/login', 'lecturer/login', 'register'])) {
                return $next($request);
            }

            if (str_starts_with($path, 'admin')) {
                return redirect()->route('admin.login');
            } elseif (str_starts_with($path, 'lecturer')) {
                return redirect()->route('lecturer.login');
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
