<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use App\Providers\AppServiceProvider;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\Permission\PermissionServiceProvider;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'auth' => Authenticate::class,
            'rate.limit' => RateLimitMiddleware::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->api(prepend: [
            RequestIdMiddleware::class,
        ]);

        $middleware->web(prepend: [
            RequestIdMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());

        $exceptions->render(function (AuthorizationException $e) {
            return response()->json([
                'message' => 'This action is unauthorized.',
                'error' => 'unauthorized',
            ], 403);
        });

        $exceptions->render(function (AccessDeniedHttpException $e) {
            return response()->json([
                'message' => 'This action is unauthorized.',
                'error' => 'unauthorized',
            ], 403);
        });

        $exceptions->render(function (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'unauthorized',
            ], 403);
        });

        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
                'error' => 'validation_error',
            ], 422);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return response()->json([
                'message' => 'The requested resource was not found.',
                'error' => 'not_found',
            ], 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e) {
            return response()->json([
                'message' => 'The HTTP method is not allowed for this route.',
                'error' => 'method_not_allowed',
            ], 405);
        });

        $exceptions->render(function (AuthenticationException $e) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'unauthenticated',
            ], 401);
        });

        $exceptions->render(function (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'domain_error',
            ], 422);
        });

        $exceptions->render(function (Throwable $e) {
            if (app()->environment('production')) {
                return response()->json([
                    'message' => 'An unexpected error occurred.',
                    'error' => 'internal_server_error',
                ], 500);
            }

            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'internal_server_error',
                'trace' => $e->getTrace(),
            ], 500);
        });
    })
    ->withProviders([
        AppServiceProvider::class,
        PermissionServiceProvider::class,
    ])
    ->create();
