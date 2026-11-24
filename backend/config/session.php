<?php

return [
    'default' => env('SESSION_DRIVER', 'redis'),

    'lifetime' => env('SESSION_LIFETIME', 120),

    'expire_on_close' => false,

    'encrypt' => env('SESSION_ENCRYPT', false),

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION', 'redis'),

    'table' => 'sessions',

    'store' => env('SESSION_STORE', 'redis'),

    'lottery' => [2, 100],

    'cookie' => env('SESSION_COOKIE', 'auction_session'),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN', null),

    'secure' => env('SESSION_SECURE_COOKIE', false),

    'http_only' => true,

    'same_site' => 'lax',

    'partitioned' => false,
];