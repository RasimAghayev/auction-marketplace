<?php

return [
    'default' => env('QUEUE_CONNECTION', 'redis'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('QUEUE_DATABASE_CONNECTION'),
            'table' => env('QUEUE_DATABASE_TABLE', 'jobs'),
            'queue' => env('QUEUE_DATABASE_QUEUE', 'default'),
            'retry_after' => (int) env('QUEUE_DATABASE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('QUEUE_REDIS_CONNECTION', 'default'),
            'queue' => env('QUEUE_REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('QUEUE_REDIS_RETRY_AFTER', 90),
            'block_for' => null,
            'after_commit' => false,
        ],
    ],

    'batching' => [
        'database' => [
            'driver' => 'database',
            'connection' => env('QUEUE_DATABASE_CONNECTION'),
            'table' => env('QUEUE_DATABASE_BATCH_TABLE', 'job_batches'),
        ],
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database'),
        'database' => env('QUEUE_FAILED_DATABASE_CONNECTION'),
        'table' => env('QUEUE_FAILED_DATABASE_TABLE', 'failed_jobs'),
    ],
];