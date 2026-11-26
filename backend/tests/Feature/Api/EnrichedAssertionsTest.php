<?php

use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
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

describe('Enriched API assertions', function () {
    test('register persists the user and returns a token', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);
    });

    test('seller creates a product that is persisted with the JSON payload', function () {
        Sanctum::actingAs($this->seller);

        $response = $this->postJson('/api/v1/products', [
            'title' => 'New Product',
            'description' => 'A description',
            'starting_price' => 50.00,
            'condition' => 'new',
            'category_id' => $this->category->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'New Product']);

        $this->assertDatabaseHas('products', [
            'title' => 'New Product',
            'seller_id' => $this->seller->id,
        ]);
    });

    test('placing a bid persists the bid and updates the auction price', function () {
        $bidder = User::factory()->create([
            'name' => 'Bidder',
            'email' => 'bidder@example.com',
            'password' => 'password123',
            'role' => UserRole::User->value,
            'is_active' => true,
        ]);
        assignRole($bidder, 'user');

        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Active,
            'current_price' => 100.00,
            'min_bid_increment' => 10.00,
            'start_date' => Carbon::now()->subHour(),
            'end_date' => Carbon::now()->addHours(24),
        ]);

        Sanctum::actingAs($bidder);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/bids", [
            'amount' => 150.00,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['amount' => 150.00]);

        $this->assertDatabaseHas('bids', [
            'auction_id' => $auction->id,
            'user_id' => $bidder->id,
            'status' => BidStatus::Accepted->value,
        ]);

        $this->assertEquals(150.00, (float) $auction->fresh()->current_price);
    });
});