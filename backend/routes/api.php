<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\AuctionController;
use App\Http\Controllers\Api\V1\BidController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\AdminController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
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
        Route::post('/auctions', [AuctionController::class, 'store']);
        Route::put('/auctions/{auction}', [AuctionController::class, 'update']);
        Route::post('/auctions/{auction}/start', [AuctionController::class, 'start']);
        Route::post('/auctions/{auction}/cancel', [AuctionController::class, 'cancel']);

        // Bids
        Route::post('/auctions/{auction}/bids', [BidController::class, 'store']);
        Route::get('/my-bids', [BidController::class, 'myBids']);

        // Notifications
        Route::get('/notifications', [DashboardController::class, 'notifications']);
        Route::put('/notifications/{notification}/read', [DashboardController::class, 'markNotificationRead']);
        Route::put('/notifications/read-all', [DashboardController::class, 'markAllNotificationsRead']);
    });

    // Admin routes
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::put('/users/{user}/suspend', [AdminController::class, 'suspendUser']);
        Route::put('/users/{user}/unsuspend', [AdminController::class, 'unsuspendUser']);
        Route::get('/products', [AdminController::class, 'products']);
        Route::put('/products/{product}/moderate', [AdminController::class, 'moderateProduct']);
        Route::get('/auctions', [AdminController::class, 'auctions']);
        Route::put('/auctions/{auction}/manage', [AdminController::class, 'manageAuction']);
        Route::get('/categories', [AdminController::class, 'categories']);
        Route::post('/categories', [AdminController::class, 'storeCategory']);
        Route::put('/categories/{category}', [AdminController::class, 'updateCategory']);
        Route::delete('/categories/{category}', [AdminController::class, 'deleteCategory']);
        Route::get('/reports', [AdminController::class, 'reports']);
    });
});