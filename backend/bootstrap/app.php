<?php

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Notification;
use App\Models\Product;
use App\Policies\AuctionPolicy;
use App\Policies\BidPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'This action is unauthorized.',
                'error' => 'unauthorized'
            ], 403);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            return response()->json([
                'message' => 'This action is unauthorized.',
                'error' => 'unauthorized'
            ], 403);
        });

        $exceptions->render(function (UnauthorizedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'unauthorized'
            ], 403);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
                'error' => 'validation_error'
            ], 422);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json([
                'message' => 'The requested resource was not found.',
                'error' => 'not_found'
            ], 404);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
            return response()->json([
                'message' => 'The HTTP method is not allowed for this route.',
                'error' => 'method_not_allowed'
            ], 405);
        });

        $exceptions->render(function (\Throwable $e) {
            if (app()->environment('production')) {
                return response()->json([
                    'message' => 'An unexpected error occurred.',
                    'error' => 'internal_server_error'
                ], 500);
            }

            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'internal_server_error',
                'trace' => $e->getTrace()
            ], 500);
        });
    })
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
        \Spatie\Permission\PermissionServiceProvider::class,
    ])
    ->create();