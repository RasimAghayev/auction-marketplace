<?php

use App\Models\User;
use App\Models\Notification;
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
});

describe('Notifications Module', function () {
    test('user can get their notifications', function () {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);
        Notification::factory()->create([
            'user_id' => 'other-user',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    });

    test('user cannot see other users notifications', function () {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'new_bid',
        ]);
        Notification::factory()->create([
            'user_id' => 'other-user',
            'type' => 'new_bid',
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200);

        $data = $response->json('data');
        foreach ($data as $notification) {
            expect($notification['user_id'])->toBe((string) $this->user->id);
        }
    });

    test('user can mark a notification as read', function () {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        expect($notification->fresh()->isRead())->toBeTrue();
    });

    test('user can mark all notifications as read', function () {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200);
        expect(Notification::where('user_id', $this->user->id)->unread()->count())->toBe(0);
    });

    test('unauthenticated user cannot get notifications', function () {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    });
});
