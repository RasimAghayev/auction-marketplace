<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Enums\UserRole;
use App\Enums\ProductStatus;
use App\Enums\ProductCondition;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\DB;

uses(LaravelTestCase::class);

beforeEach(function () {
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
});

describe('Products Module', function () {
    test('anyone can list published products', function () {
        Product::factory()->count(3)->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Published->value,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    });

    test('anyone can view a product', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Published->value,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->slug}");

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => $product->title]);
    });

    test('seller can create a product', function () {
        Sanctum::actingAs($this->seller);

        $response = $this->postJson('/api/v1/products', [
            'title' => 'Test Product',
            'description' => 'Test description',
            'category_id' => $this->category->id,
            'starting_price' => 100.00,
            'condition' => 'new',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['title' => 'Test Product']);
    });

    test('non-seller cannot create a product', function () {
        $buyer = User::factory()->create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => 'password123',
            'role' => UserRole::User->value,
            'is_active' => true,
        ]);

        Sanctum::actingAs($buyer);

        $response = $this->postJson('/api/v1/products', [
            'title' => 'Test Product',
            'description' => 'Test description',
            'category_id' => $this->category->id,
            'starting_price' => 100.00,
            'condition' => 'new',
        ]);

        $response->assertStatus(403);
    });

    test('seller can update own product', function () {
        Sanctum::actingAs($this->seller);

        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Draft->value,
        ]);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'Updated Title']);
    });

    test('seller cannot delete a published product', function () {
        Sanctum::actingAs($this->seller);

        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Published->value,
        ]);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(422);
    });

    test('seller can delete a draft product', function () {
        Sanctum::actingAs($this->seller);

        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Draft->value,
        ]);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);
    });

    test('products can be filtered by category', function () {
        Product::factory()->count(2)->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Published->value,
        ]);

        $response = $this->getJson("/api/v1/products?category={$this->category->slug}");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    });
});
