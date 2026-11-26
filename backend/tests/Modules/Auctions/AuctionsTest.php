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
use App\Modules\Auctions\Repositories\AuctionRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(LaravelTestCase::class);

beforeEach(function () {
    cleanTestDatabase();
    seedTestRoles();
    Cache::flush();

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

    $this->product = Product::factory()->create([
        'seller_id' => $this->seller->id,
        'category_id' => $this->category->id,
        'title' => 'Test Product',
        'slug' => 'test-product',
        'status' => ProductStatus::Published->value,
    ]);
});

describe('Auctions Module', function () {
    test('seller can create an auction', function () {
        Sanctum::actingAs($this->seller);

        $response = $this->postJson('/api/v1/auctions', [
            'product_id' => $this->product->id,
            'starting_price' => 100.00,
            'min_bid_increment' => 10.00,
            'reserve_price' => 150.00,
            'start_date' => Carbon::now()->addMinutes(5)->toISOString(),
            'end_date' => Carbon::now()->addHours(24)->toISOString(),
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['status' => 'scheduled']);
    });

    test('non-seller cannot create an auction', function () {
        $buyer = User::factory()->create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => 'password123',
            'role' => UserRole::User->value,
            'is_active' => true,
        ]);

        Sanctum::actingAs($buyer);

        $response = $this->postJson('/api/v1/auctions', [
            'product_id' => $this->product->id,
            'starting_price' => 100.00,
        ]);

        $response->assertStatus(403);
    });

    test('anyone can list auctions', function () {
        Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Scheduled,
        ]);

        $response = $this->getJson('/api/v1/auctions');

        $response->assertStatus(200);
    });

    test('anyone can view an auction', function () {
        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Scheduled,
        ]);

        $response = $this->getJson("/api/v1/auctions/{$auction->id}");

        $response->assertStatus(200);
    });

    test('seller can start a scheduled auction', function () {
        Sanctum::actingAs($this->seller);

        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Scheduled,
            'start_date' => Carbon::now()->subMinutes(5),
            'end_date' => Carbon::now()->addHours(24),
        ]);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/start");

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'active']);
    });

    test('seller cannot start an active auction', function () {
        Sanctum::actingAs($this->seller);

        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Active,
        ]);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/start");

        $response->assertStatus(422);
    });

    test('seller can cancel a scheduled auction', function () {
        Sanctum::actingAs($this->seller);

        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Scheduled,
        ]);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/cancel");

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'cancelled']);
    });

    test('active auctions are cached', function () {
        Cache::flush();

        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Active,
        ]);

        $activeAuctions = app(AuctionRepository::class)->active();

        expect(count($activeAuctions))->toBeGreaterThan(0);

        Cache::forget('auctions:active');
    });

    test('seller can complete an active auction with winning bid', function () {
        Sanctum::actingAs($this->seller);

        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Active,
            'current_price' => 150.00,
            'start_date' => Carbon::now()->subHour(),
            'end_date' => Carbon::now()->subMinutes(30),
        ]);

        $bid = Bid::factory()->create([
            'auction_id' => $auction->id,
            'user_id' => User::factory()->create([
                'email' => 'bidder@example.com',
                'role' => UserRole::User->value,
                'is_active' => true,
            ])->id,
            'amount' => 150.00,
            'status' => BidStatus::Accepted,
        ]);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/complete");

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'completed']);
    });

    test('seller can complete an active auction without bids (unsold)', function () {
        Sanctum::actingAs($this->seller);

        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Active,
            'current_price' => 100.00,
            'start_date' => Carbon::now()->subHour(),
            'end_date' => Carbon::now()->subMinutes(30),
        ]);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/complete");

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'completed']);
    });

    test('non-seller cannot complete an auction', function () {
        $buyer = User::factory()->create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
            'password' => 'password123',
            'role' => UserRole::User->value,
            'is_active' => true,
        ]);

        Sanctum::actingAs($buyer);

        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Active,
        ]);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/complete");

        $response->assertStatus(403);
    });

    test('cannot complete an already completed auction', function () {
        Sanctum::actingAs($this->seller);

        $auction = Auction::factory()->create([
            'product_id' => $this->product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Completed,
        ]);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/complete");

        $response->assertStatus(422);
    });
});
