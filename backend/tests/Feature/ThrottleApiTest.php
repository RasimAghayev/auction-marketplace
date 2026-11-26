<?php

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

it('registers the api rate limiter', function () {
    expect(RateLimiter::limiter('api'))->not->toBeNull();
});

it('applies the throttle:api middleware to the v1 group', function () {
    $hasThrottle = collect(Route::getRoutes())
        ->contains(fn ($route) => $route->uri() === 'api/v1/auth/login'
            && in_array('throttle:api', $route->gatherMiddleware(), true));

    expect($hasThrottle)->toBeTrue();
});
