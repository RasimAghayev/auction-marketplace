<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Auction;
use App\Models\Bid;
use App\Enums\UserRole;
use App\Enums\ProductStatus;
use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
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

    $this->buyer = User::factory()->create([
        'name' => 'Buyer User',
        'email' => 'buyer@example.com',
        'password' => 'password123',
        'role' => UserRole::User->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);

    $buyerRoleId = DB::connection('mongodb_testing')->table('roles')->where('name', 'user')->value('id');
    DB::connection('mongodb_testing')->table('model_has_roles')->insert([
        'model_id' => $this->buyer->getKey(),
        'model_type' => User::class,
        'role_id' => $buyerRoleId,
    ]);

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

    $this->auction = Auction::factory()->create([
        'product_id' => $this->product->id,
        'seller_id' => $this->seller->id,
        'status' => AuctionStatus::Active,
        'current_price' => 100.00,
        'start_date' => Carbon::now()->subMinutes(5),
        'end_date' => Carbon::now()->addHours(24),
    ]);
});

describe('Bids Module', function () {
    test('buyer can place a bid on an active auction', function () {
        Sanctum::actingAs($this->buyer);

        $response = $this->postJson("/api/v1/auctions/{$this->auction->id}/bids", [
            'amount' => 110.00,
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['amount' => 110.00]);
    });

    test('buyer cannot bid on a scheduled auction', function () {
        $scheduledAuction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Scheduled,
            'current_price' => 100.00,
        ]);

        Sanctum::actingAs($this->buyer);

        $response = $this->postJson("/api/v1/auctions/{$scheduledAuction->id}/bids", [
            'amount' => 110.00,
        ]);

        $response->assertStatus(422);
    });

    test('buyer cannot place a bid lower than current price', function () {
        Sanctum::actingAs($this->buyer);

        $response = $this->postJson("/api/v1/auctions/{$this->auction->id}/bids", [
            'amount' => 50.00,
        ]);

        $response->assertStatus(422);
    });

    test('buyer can view their bids', function () {
        Sanctum::actingAs($this->buyer);

        $this->postJson("/api/v1/auctions/{$this->auction->id}/bids", [
            'amount' => 110.00,
        ]);

        $response = $this->getJson('/api/v1/my-bids');

        $response->assertStatus(200);
    });

    test('auction current price updates when bid is placed', function () {
        Sanctum::actingAs($this->buyer);

        $this->postJson("/api/v1/auctions/{$this->auction->id}/bids", [
            'amount' => 110.00,
        ]);

        $this->auction->refresh();
        expect($this->auction->current_price)->toBe(110.00);
    });

    test('bid total_bids count increments', function () {
        Sanctum::actingAs($this->buyer);

        $this->postJson("/api/v1/auctions/{$this->auction->id}/bids", [
            'amount' => 110.00,
        ]);

        $this->auction->refresh();
        expect($this->auction->total_bids)->toBeGreaterThan(0);
    });
});
