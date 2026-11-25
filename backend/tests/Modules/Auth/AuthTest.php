<?php

use App\Models\User;
use App\Models\Role;
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

describe('Auth Module', function () {
    test('user can register successfully', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email'],
            'token',
        ]);

        expect(User::where('email', 'newuser@example.com')->exists())->toBeTrue();
    });

    test('user cannot register with duplicate email', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Duplicate',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    });

    test('user can login with valid credentials', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
            'token',
        ]);
    });

    test('user cannot login with invalid password', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    });

    test('authenticated user can get own profile', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(200);
        $response->assertJsonFragment(['email' => 'test@example.com']);
    });

    test('unauthenticated user cannot get profile', function () {
        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(401);
    });

    test('authenticated user can logout', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
    });

    test('user can update profile', function () {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/auth/profile', [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Updated Name']);
    });

    test('user can update password', function () {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
    });

    test('user cannot update password with wrong current password', function () {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/auth/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
    });

    test('admin can access admin dashboard', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'users_count',
            'products_count',
            'auctions_count',
            'categories_count',
        ]);
    });

    test('non-admin cannot access admin dashboard', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(403);
    });
});
