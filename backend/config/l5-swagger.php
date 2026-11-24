<?php

return [
    'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
    'generate_yaml_copy' => env('L5_SWAGGER_GENERATE_YAML_COPY', false),
    'proxy' => env('L5_SWAGGER_PROXY', false),
    'scan' => [
        'paths' => [
            base_path('app/Http/Controllers/Api'),
        ],
        'ignored' => [
            base_path('vendor'),
        ],
        'patterns' => [
            '*.php',
        ],
        'scan_only_if_no_file' => env('L5_SWAGGER_SCAN_ONLY_IF_NO_FILE', false),
    ],
    'constants' => [
        'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', 'http://localhost:8000'),
    ],
    'versions' => [
        'v1' => [
            'api_version' => 'v1',
            'title' => 'Auction Marketplace API',
            'description' => 'API documentation for Auction Marketplace',
            'terms_of_service' => 'https://auction.local/terms',
            'contact' => [
                'name' => 'API Support',
                'email' => 'api@auction.local',
            ],
            'license' => [
                'name' => 'MIT',
                'url' => 'https://opensource.org/licenses/MIT',
            ],
            'proxy' => false,
        ],
    ],
    'defaults' => [
        'routes' => [
            'api' => 'api/v1',
            'docs' => 'docs/v1',
            'oauth2_callback' => 'api/v1/oauth2/callback',
        ],
        'paths' => [
            'docs' => storage_path('api-docs'),
            'docs_json' => 'docs.json',
            'docs_yaml' => 'docs.yaml',
            'annotations' => base_path('app/Http/Controllers/Api'),
            'base' => env('L5_SWAGGER_BASE_PATH', null),
            'excludes' => [],
        ],
        'scan_options' => [
            'analysis' => ['include' => ['*/Http/Controllers/Api/*']],
        ],
        'proxy' => env('L5_SWAGGER_PROXY', false),
    ],
    'ui' => [
        'display' => [
            'dark_mode' => true,
            'doc_expansion' => 'none',
            'filter' => true,
            'layout' => 'BaseLayout',
        ],
        'auth' => [
            'basic' => [
                'enabled' => false,
            ],
        ],
    ],
    'securityDefinitions' => [
        'sanctum' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'Authorization',
            'description' => 'Enter your Bearer token in the format **Bearer <token>**',
        ],
    ],
];