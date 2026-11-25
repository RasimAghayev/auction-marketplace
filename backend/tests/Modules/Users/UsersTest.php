<?php

use App\Models\User;
use App\Enums\UserRole;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\DB;

uses(LaravelTestCase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'role' => UserRole::User->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);

    $userRoleId = DB::connection('mongodb_testing')->table('roles')->where('name', 'user')->value('id');
    DB::connection('mongodb_testing')->table('model_has_roles')->insert([
        'model_id' => $this->user->getKey(),
        'model_type' => User::class,
        'role_id' => $userRoleId,
    ]);

    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => UserRole::Admin->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);

    $adminRoleId = DB::connection('mongodb_testing')->table('roles')->where('name', 'admin')->value('id');
    DB::connection('mongodb_testing')->table('model_has_roles')->insert([
        'model_id' => $this->admin->getKey(),
        'model_type' => User::class,
        'role_id' => $adminRoleId,
    ]);
});

describe('Users Module', function () {
    test('user can view own profile', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/users/{$this->user->id}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => 'test@example.com']);
    });

    test('user can view another user profile', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/users/{$this->admin->id}");

        $response->assertStatus(200);
    });

    test('user can update own profile', function () {
        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/users/{$this->user->id}", [
            'name' => 'Updated Name',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Updated Name']);
    });

    test('user cannot update another user profile', function () {
        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/users/{$this->admin->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403);
    });

    test('user can update own password', function () {
        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/users/{$this->user->id}/password", [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
    });

    test('admin can list users', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(200);
    });

    test('admin can suspend a user', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/admin/users/{$this->user->id}/suspend");

        $response->assertStatus(200);
        $response->assertJsonFragment(['is_active' => false]);
    });

    test('admin cannot suspend another admin', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/admin/users/{$this->admin->id}/suspend");

        $response->assertStatus(403);
    });

    test('admin can unsuspend a user', function () {
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/admin/users/{$this->user->id}/suspend");

        $response = $this->putJson("/api/v1/admin/users/{$this->user->id}/unsuspend");

        $response->assertStatus(200);
        $response->assertJsonFragment(['is_active' => true]);
    });

    test('non-admin cannot list users', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    });
});
