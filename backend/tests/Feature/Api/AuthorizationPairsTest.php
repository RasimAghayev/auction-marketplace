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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

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
    assignRole($this->seller, 'seller');

    $this->buyer = User::factory()->create([
        'name' => 'Buyer',
        'email' => 'buyer@example.com',
        'password' => 'password123',
        'role' => UserRole::User->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
    assignRole($this->buyer, 'user');

    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => UserRole::Admin->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
    assignRole($this->admin, 'admin');

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

describe('Authorization pairs (403 before 422)', function () {
    test('non-owner cannot upload product images (403)', function () {
        Sanctum::actingAs($this->buyer);

        $response = $this->postJson("/api/v1/products/{$this->product->id}/images", [
            'images' => [UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg')],
        ]);

        $response->assertStatus(403);
    });

    test('owner without images fails validation (422)', function () {
        Sanctum::actingAs($this->seller);

        $response = $this->postJson("/api/v1/products/{$this->product->id}/images", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('images');
    });

    test('non-admin cannot manage an auction (403)', function () {
        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Scheduled,
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->putJson("/api/v1/admin/auctions/{$auction->id}/manage", [
            'action' => 'cancel',
        ]);

        $response->assertStatus(403);
    });

    test('admin can manage (cancel) a scheduled auction (200)', function () {
        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Scheduled,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/admin/auctions/{$auction->id}/manage", [
            'action' => 'cancel',
        ]);

        $response->assertStatus(200);
    });

    test('non-admin cannot moderate a product (403)', function () {
        Sanctum::actingAs($this->buyer);

        $response = $this->putJson("/api/v1/admin/products/{$this->product->id}/moderate", [
            'action' => 'approve',
        ]);

        $response->assertStatus(403);
    });

    test('admin can moderate (approve) a product (200)', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/admin/products/{$this->product->id}/moderate", [
            'action' => 'approve',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(ProductStatus::Published, $this->product->fresh()->status);
    });
});