<?php

use App\Http\Controllers\Api\V1\AdminAuctionController;
use App\Http\Controllers\Api\V1\AdminCategoryController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AdminProductController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\AuctionController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BidController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function () {
    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register'])->middleware('rate.limit:5,1');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('rate.limit:10,1');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('rate.limit:3,1');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('rate.limit:3,1');
    Route::get('/auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('/auth/verification-notification', [AuthController::class, 'sendVerificationEmail'])->middleware('auth:sanctum');

    // Categories (public)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);

    // Public products and auctions
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/auctions', [AuctionController::class, 'index']);
    Route::get('/auctions/{auction}', [AuctionController::class, 'show']);
    Route::get('/auctions/{auction}/bids', [BidController::class, 'index']);

    // Search
    Route::get('/search/products', [ProductController::class, 'search']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::put('/auth/password', [AuthController::class, 'updatePassword']);

        // User profiles
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::put('/users/{user}/password', [UserController::class, 'updatePassword']);

        // User dashboard
        Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
        Route::get('/dashboard/products', [DashboardController::class, 'products']);
        Route::get('/dashboard/auctions', [DashboardController::class, 'auctions']);
        Route::get('/dashboard/participated', [DashboardController::class, 'participated']);
        Route::get('/dashboard/won', [DashboardController::class, 'won']);
        Route::get('/dashboard/history', [DashboardController::class, 'history']);
        Route::get('/dashboard/bids', [DashboardController::class, 'bids']);

        // Products
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        Route::post('/products/{product}/images', [ProductController::class, 'uploadImages']);
        Route::delete('/products/{product}/images/{image}', [ProductController::class, 'deleteImage']);

        // Auctions
        Route::post('/auctions', [AuctionController::class, 'store'])->middleware('rate.limit:10,1');
        Route::put('/auctions/{auction}', [AuctionController::class, 'update'])->middleware('rate.limit:10,1');
        Route::post('/auctions/{auction}/start', [AuctionController::class, 'start'])->middleware('rate.limit:10,1');
        Route::post('/auctions/{auction}/cancel', [AuctionController::class, 'cancel'])->middleware('rate.limit:10,1');
        Route::post('/auctions/{auction}/complete', [AuctionController::class, 'complete'])->middleware('rate.limit:10,1');

        // Bids
        Route::post('/auctions/{auction}/bids', [BidController::class, 'store'])->middleware('rate.limit:30,1');
        Route::get('/my-bids', [BidController::class, 'myBids']);

        // Notifications
        Route::get('/notifications', [DashboardController::class, 'notifications']);
        Route::put('/notifications/{notification}/read', [DashboardController::class, 'markNotificationRead']);
        Route::put('/notifications/read-all', [DashboardController::class, 'markAllNotificationsRead']);
    });

    // Admin routes
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::put('/users/{user}/suspend', [AdminUserController::class, 'suspend']);
        Route::put('/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend']);
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::put('/products/{product}/moderate', [AdminProductController::class, 'moderate']);
        Route::get('/auctions', [AdminAuctionController::class, 'index']);
        Route::put('/auctions/{auction}/manage', [AdminAuctionController::class, 'manage']);
        Route::get('/categories', [AdminCategoryController::class, 'index']);
        Route::post('/categories', [AdminCategoryController::class, 'store']);
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);
        Route::get('/reports', [AdminController::class, 'reports']);
    });
});
