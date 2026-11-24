<?php

return [
    'default' => env('CACHE_DRIVER', 'redis'),

    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('CACHE_DATABASE_CONNECTION'),
            'table' => 'cache',
            'lock_connection' => env('CACHE_DATABASE_LOCK_CONNECTION'),
            'lock_table' => 'cache_locks',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('CACHE_REDIS_CONNECTION', 'cache'),
            'lock_connection' => env('CACHE_REDIS_LOCK_CONNECTION', 'default'),
            'prefix' => env('CACHE_PREFIX', 'auction_'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                Memcached::OPT_CONNECT_TIMEOUT => 2000,
                Memcached::OPT_RETRY_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'auction_'),
];