<?php

use App\Enums\AuctionStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Laravel\Sanctum\Sanctum;

uses(LaravelTestCase::class);

beforeEach(function () {
    cleanTestDatabase();
    seedTestRoles();

    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => UserRole::Admin->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
    assignRole($this->admin, 'admin');

    $this->seller = User::factory()->create([
        'name' => 'Seller User',
        'email' => 'seller@example.com',
        'password' => 'password123',
        'role' => UserRole::Seller->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
    assignRole($this->seller, 'seller');

    $this->category = Category::factory()->create([
        'name' => 'Electronics',
        'slug' => 'electronics',
        'is_active' => true,
    ]);

    $this->product = Product::factory()->create([
        'seller_id' => $this->seller->id,
        'category_id' => $this->category->id,
        'title' => 'Test Product',
        'slug' => 'test-product',
        'status' => ProductStatus::Published->value,
    ]);
});

describe('Admin controllers', function () {
    test('admin can fetch reports with a stable shape', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/reports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'daily_registrations',
                'daily_auctions',
                'top_categories',
                'top_sellers',
            ]);
    });

    test('admin can suspend and unsuspend a user', function () {
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/admin/users/{$this->seller->id}/suspend")
            ->assertStatus(200);

        $this->putJson("/api/v1/admin/users/{$this->seller->id}/unsuspend")
            ->assertStatus(200);
    });

    test('admin can list users and products', function () {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/users')->assertStatus(200);
        $this->getJson('/api/v1/admin/products')->assertStatus(200);
    });

    test('admin can create, update and delete a category', function () {
        Sanctum::actingAs($this->admin);

        $created = $this->postJson('/api/v1/admin/categories', [
            'name' => 'Books',
            'slug' => 'books',
        ]);
        $created->assertStatus(201);
        $id = $created->json('category.id');

        $this->putJson("/api/v1/admin/categories/{$id}", [
            'name' => 'Books & Media',
            'slug' => 'books',
        ])->assertStatus(200);

        $this->deleteJson("/api/v1/admin/categories/{$id}")
            ->assertStatus(204);
    });

    test('admin cannot delete a category with children', function () {
        $child = Category::factory()->create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->admin);

        $this->deleteJson("/api/v1/admin/categories/{$this->category->id}")
            ->assertStatus(422);
    });

    test('admin can extend an auction end date', function () {
        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Active,
            'end_date' => now()->addDay(),
        ]);

        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/admin/auctions/{$auction->id}/manage", [
            'action' => 'extend',
            'end_date' => now()->addDays(2)->toIso8601String(),
        ])->assertStatus(200);
    });
});
