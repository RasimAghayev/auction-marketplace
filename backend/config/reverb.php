<?php

return [
    'apps' => [
        [
            'id' => env('REVERB_APP_ID', 'auction-app'),
            'name' => env('APP_NAME', 'Auction Marketplace'),
            'key' => env('REVERB_APP_KEY', 'auction-key'),
            'secret' => env('REVERB_APP_SECRET', 'auction-secret'),
            'enable_client_messages' => false,
            'enable_statistics' => true,
            'path' => env('REVERB_PATH', '/app'),
            'max_message_size_kb' => 16,
        ],
    ],

    'host' => env('REVERB_HOST', '0.0.0.0'),

    'port' => env('REVERB_PORT', 8080),

    'scheme' => env('REVERB_SCHEME', 'http'),

    'ssl' => [
        'local_cert' => env('REVERB_SSL_LOCAL_CERT'),
        'local_pk' => env('REVERB_SSL_LOCAL_PK'),
        'passphrase' => env('REVERB_SSL_PASSPHRASE'),
        'verify_peer' => false,
    ],

    'statistics' => [
        'driver' => env('REVERB_STATISTICS_DRIVER', 'redis'),
        'connection' => env('REVERB_STATISTICS_REDIS_CONNECTION', 'default'),
        'interval_sec' => env('REVERB_STATISTICS_INTERVAL_SEC', 10),
        'table' => env('REVERB_STATISTICS_TABLE', 'reverb_statistics'),
    ],

    'cors' => [
        'enabled' => env('REVERB_CORS_ENABLED', true),
        'allowed_origins' => env('REVERB_CORS_ALLOWED_ORIGINS', '*'),
        'allowed_origin_patterns' => [],
        'allowed_headers' => ['*'],
        'allowed_methods' => ['GET', 'POST'],
        'supports_credentials' => true,
        'max_age' => 86400,
    ],

    'debug' => env('REVERB_DEBUG', false),

    'allowed_origin_patterns' => env('REVERB_ALLOWED_ORIGIN_PATTERNS', []),

    'max_request_size_kb' => env('REVERB_MAX_REQUEST_SIZE_KB', 256),

    'worker' => [
        'max_message_size_kb' => 16,
        'max_connections' => env('REVERB_MAX_CONNECTIONS', 10000),
        'memory_limit' => '256M',
    ],
];