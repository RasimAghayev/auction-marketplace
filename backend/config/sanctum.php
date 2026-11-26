<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),

    'guard' => ['web'],

    'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 10080),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'verify_csrf_token' => VerifyCsrfToken::class,
    ],
];
