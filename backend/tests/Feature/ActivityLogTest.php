<?php

use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Laravel\Sanctum\Sanctum;

uses(LaravelTestCase::class);

beforeEach(function () {
    cleanTestDatabase();
    seedTestRoles();

    $this->user = User::factory()->create([
        'name' => 'Auth User',
        'email' => 'auth@example.com',
        'password' => 'password123',
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
});

describe('Security activity log', function () {
    test('login is recorded as an activity', function () {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'auth@example.com',
            'password' => 'password123',
        ])->assertStatus(200);

        expect(Activity::where('event', 'auth.login')->exists())->toBeTrue();
    });

    test('logout is recorded as an activity', function () {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/auth/logout')->assertStatus(200);

        expect(Activity::where('event', 'auth.logout')->exists())->toBeTrue();
    });

    test('password update is recorded as an activity', function () {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/auth/password', [
            'current_password' => 'password123',
            'password' => 'Newpassword123!',
            'password_confirmation' => 'Newpassword123!',
        ])->assertStatus(200);

        expect(Activity::where('event', 'auth.password_updated')->exists())->toBeTrue();
    });
});
