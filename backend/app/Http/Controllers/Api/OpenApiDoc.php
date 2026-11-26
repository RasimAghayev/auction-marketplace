<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes\Contact;
use OpenApi\Attributes\Info;
use OpenApi\Attributes\License;
use OpenApi\Attributes\OpenApi;
use OpenApi\Attributes\Server;

#[OpenApi(
    info: new Info(
        version: '1.0.0',
        title: 'Auction Marketplace API',
        description: 'REST API for the distributed auction marketplace backend.',
        contact: new Contact(name: 'API Support', email: 'support@example.com'),
        license: new License(name: 'MIT'),
    ),
    servers: [
        new Server(url: '/api/v1', description: 'Version 1 API'),
    ],
)]
class OpenApiDoc extends Controller {}
