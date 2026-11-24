<?php

return [
    'paths' => [
        resource_path('views'),
    ],

    'compiled' => realpath(storage_path('framework/views')),

    'engines' => [
        '.blade.php' => 'blade',
        '.php' => 'php',
    ],
];