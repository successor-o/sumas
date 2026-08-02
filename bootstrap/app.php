<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\RedirectIfUnauthenticated;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:     __DIR__ . '/../routes/web.php',
        api:     __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health:  '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // CORS — allow frontend origin
        $middleware->statefulApi();

        // Register custom role middleware
        $middleware->alias([
            'role' => CheckRole::class,
            'auth.redirect' => RedirectIfUnauthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Return JSON for all API errors
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || str_starts_with($request->getPathInfo(), '/api')) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                return response()->json([
                    'message' => $e->getMessage() ?: 'Server error.',
                    'error'   => class_basename($e),
                ], $status);
            }
        });
    })
    ->create();
