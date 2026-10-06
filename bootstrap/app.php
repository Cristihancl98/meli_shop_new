<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\IdentifyTenantFromSession::class]);

        $middleware->alias([
            'tenant.code' => \App\Http\Middleware\IdentifyTenantFromCode::class,
            'auth.jwt' => \App\Http\Middleware\JwtMiddleware::class,
            'web.auth' => \App\Http\Middleware\WebAuthMiddleware::class,
            'role'     => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], $e->status);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return response()->json([
                    'success' => false,
                    'data'    => null,
                    'message' => 'No tienes permisos para realizar esta acción.',
                    'errors'  => [],
                ], 403);
            }
        });
    })->create();
