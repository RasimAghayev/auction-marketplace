<?php

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

uses(LaravelTestCase::class);

describe('Request ID middleware', function () {
    test('generates a request id header when none is provided', function () {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200);
        expect($response->headers->get('X-Request-ID'))->not->toBeNull();
    });

    test('propagates an incoming request id header', function () {
        $incoming = 'client-supplied-id-123';

        $response = $this->withHeaders(['X-Request-ID' => $incoming])
            ->getJson('/api/v1/categories');

        $response->assertStatus(200);
        expect($response->headers->get('X-Request-ID'))->toBe($incoming);
    });

    test('rejects a malformed incoming request id and generates a new one', function () {
        $response = $this->withHeaders(['X-Request-ID' => 'bad id with spaces'])
            ->getJson('/api/v1/categories');

        $response->assertStatus(200);
        expect($response->headers->get('X-Request-ID'))->not->toBe('bad id with spaces');
    });
});
