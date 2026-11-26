<?php

use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\DB;

uses(LaravelTestCase::class);

beforeEach(function () {
    cleanTestDatabase();
    seedTestRoles();

    $this->seller = User::factory()->create([
        'name' => 'Seller User',
        'email' => 'seller@example.com',
        'password' => 'password123',
        'role' => UserRole::Seller->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);

    $sellerRoleId = DB::connection('mongodb_testing')->table('roles')->where('name', 'seller')->value('id');
    DB::connection('mongodb_testing')->table('model_has_roles')->insert([
        'model_id' => $this->seller->getKey(),
        'model_type' => User::class,
        'role_id' => $sellerRoleId,
    ]);

    $this->category = Category::factory()->create([
        'name' => 'Electronics',
        'slug' => 'electronics',
        'is_active' => true,
    ]);

    Product::factory()->count(5)->create([
        'seller_id' => $this->seller->id,
        'category_id' => $this->category->id,
        'title' => 'iPhone 15',
        'slug' => 'iphone-15',
        'status' => ProductStatus::Published->value,
    ]);

    Product::factory()->count(3)->create([
        'seller_id' => $this->seller->id,
        'category_id' => $this->category->id,
        'title' => 'Samsung Galaxy',
        'slug' => 'samsung-galaxy',
        'status' => ProductStatus::Published->value,
    ]);
});

describe('Search Module', function () {
    test('anyone can search products by query', function () {
        $response = $this->getJson('/api/v1/search/products?q=iPhone');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
    });

    test('search returns empty when no match', function () {
        $response = $this->getJson('/api/v1/search/products?q=NonExistentProduct');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    });

    test('search supports category filter', function () {
        $response = $this->getJson("/api/v1/search/products?category={$this->category->slug}");

        $response->assertStatus(200);
    });

    test('search supports max_price filter', function () {
        $response = $this->getJson('/api/v1/search/products?q=iPhone&max_price=1000');

        $response->assertStatus(200);
    });

    test('search supports min_price filter', function () {
        $response = $this->getJson('/api/v1/search/products?q=iPhone&min_price=100');

        $response->assertStatus(200);
    });
});
