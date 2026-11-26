<?php

use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(LaravelTestCase::class);

beforeEach(function () {
    config(['cache.default' => 'array']);
    Cache::flush();

    foreach (['users', 'model_has_roles', 'personal_access_tokens'] as $collection) {
        DB::connection('mongodb_testing')->table($collection)->delete();
    }
});

test('register endpoint is rate limited to 5 requests per minute', function () {
    for ($i = 1; $i <= 5; $i++) {
        $this->postJson('/api/v1/auth/register', [
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertStatus(201);
    }

    $this->postJson('/api/v1/auth/register', [
        'name' => 'User 6',
        'email' => 'user6@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertStatus(429);
});