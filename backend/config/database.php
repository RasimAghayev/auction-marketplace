<?php

return [
    'default' => env('DB_CONNECTION', 'mongodb'),

    'connections' => [
        'mongodb' => [
            'driver' => 'mongodb',
            'dsn' => env('MONGODB_URI', 'mongodb://localhost:27017'),
            'database' => env('MONGODB_DATABASE', 'auction_marketplace'),
            'options' => array_filter([
                'replicaSet' => env('MONGODB_REPLICA_SET', ''),
            ]),
        ],

        'mongodb_testing' => [
            'driver' => 'mongodb',
            'dsn' => env('MONGODB_URI_TESTING', 'mongodb://mongodb:27017/?authSource=admin'),
            'database' => env('MONGODB_DATABASE_TESTING', 'auction_marketplace_testing'),
            'username' => env('MONGODB_USERNAME_TESTING', 'admin'),
            'password' => env('MONGODB_PASSWORD_TESTING', '4Y4fEgzujaOswRlFJcKF0nC2VpZ0ZZUB'),
            'options' => array_filter([
                'replicaSet' => env('MONGODB_REPLICA_SET_TESTING', ''),
            ]),
        ],
    ],

    'redis' => [
        'client' => env('REDIS_CLIENT', 'predis'),
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD') ?: @file_get_contents('/run/secrets/redis_password') ?: null,
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],
        'cache' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD') ?: @file_get_contents('/run/secrets/redis_password') ?: null,
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
        ],
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD') ?: @file_get_contents('/run/secrets/redis_password') ?: null,
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],
    ],
];