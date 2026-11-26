<?php

use App\Enums\AuctionStatus;
use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Laravel\Sanctum\Sanctum;

uses(LaravelTestCase::class);

beforeEach(function () {
    cleanTestDatabase();
    seedTestRoles();

    $this->users['alice'] = User::factory()->create([
        'name' => 'Alice Seller',
        'email' => 'alice@example.com',
        'password' => 'password123',
        'role' => UserRole::Seller->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
    assignRole($this->users['alice'], 'seller');

    $this->users['bob'] = User::factory()->create([
        'name' => 'Bob Bidder',
        'email' => 'bob@example.com',
        'password' => 'password123',
        'role' => UserRole::User->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
    assignRole($this->users['bob'], 'user');

    $this->users['charlie'] = User::factory()->create([
        'name' => 'Charlie Bidder',
        'email' => 'charlie@example.com',
        'password' => 'password123',
        'role' => UserRole::User->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
    assignRole($this->users['charlie'], 'user');

    $this->category = Category::factory()->create([
        'name' => 'Electronics',
        'slug' => 'electronics',
        'is_active' => true,
    ]);
});

function createAuctionFor($test, User $seller, Category $category, array $overrides = []): string
{
    Sanctum::actingAs($seller);

    $productResponse = $test->postJson('/api/v1/products', [
        'title' => $overrides['title'] ?? 'Auction Item',
        'description' => 'A test item for auction.',
        'category_id' => $category->id,
        'condition' => ProductCondition::New->value,
        'starting_price' => $overrides['starting_price'] ?? 500,
    ]);
    $productResponse->assertStatus(201);
    $productId = $productResponse->json('product.id');

    $auctionResponse = $test->postJson('/api/v1/auctions', [
        'product_id' => $productId,
        'starting_price' => $overrides['starting_price'] ?? 500,
        'min_bid_increment' => $overrides['min_bid_increment'] ?? 25,
        'reserve_price' => $overrides['reserve_price'] ?? 800,
        'start_date' => Carbon::now()->addMinutes(5)->toISOString(),
        'end_date' => Carbon::now()->addHours(1)->toISOString(),
    ]);
    $auctionResponse->assertStatus(201);
    $auctionId = $auctionResponse->json('auction.id');

    $test->postJson("/api/v1/auctions/{$auctionId}/start")->assertStatus(200);

    return $auctionId;
}

describe('Complete Auction Lifecycle - ATDD', function () {
    test('complete successful auction from listing to winner', function () {
        $auctionId = createAuctionFor($this, $this->users['alice'], $this->category, [
            'title' => 'iPhone 15 Pro',
            'starting_price' => 500,
            'min_bid_increment' => 25,
            'reserve_price' => 800,
        ]);

        Sanctum::actingAs($this->users['bob']);
        $this->postJson("/api/v1/auctions/{$auctionId}/bids", ['amount' => 550])->assertStatus(201);

        Sanctum::actingAs($this->users['charlie']);
        $this->postJson("/api/v1/auctions/{$auctionId}/bids", ['amount' => 575])->assertStatus(201);

        Sanctum::actingAs($this->users['bob']);
        $this->postJson("/api/v1/auctions/{$auctionId}/bids", ['amount' => 600])->assertStatus(201);

        Sanctum::actingAs($this->users['alice']);
        $this->postJson("/api/v1/auctions/{$auctionId}/complete")->assertStatus(200);

        $auction = Auction::find($auctionId)->refresh();
        expect($auction->status)->toBe(AuctionStatus::Completed);
        expect((string) $auction->winner_id)->toBe((string) $this->users['bob']->id);
        expect((float) $auction->current_price)->toBe(600.0);

        $bobNotifications = Notification::where('user_id', $this->users['bob']->id)->get();
        $aliceNotifications = Notification::where('user_id', $this->users['alice']->id)->get();

        expect($bobNotifications->where('type', 'auction_won')->count())->toBe(1);
        expect($aliceNotifications->where('type', 'auction_completed')->count())->toBe(1);
    });

    test('auction completes but product stays published when reserve not met', function () {
        $auctionId = createAuctionFor($this, $this->users['alice'], $this->category, [
            'title' => 'Rare Coin',
            'starting_price' => 100,
            'min_bid_increment' => 10,
            'reserve_price' => 500,
        ]);

        Sanctum::actingAs($this->users['bob']);
        $this->postJson("/api/v1/auctions/{$auctionId}/bids", ['amount' => 200])->assertStatus(201);

        Sanctum::actingAs($this->users['alice']);
        $this->postJson("/api/v1/auctions/{$auctionId}/complete")->assertStatus(200);

        $auction = Auction::find($auctionId)->refresh();
        expect($auction->status)->toBe(AuctionStatus::Completed);
        expect((string) $auction->winner_id)->toBe((string) $this->users['bob']->id);

        $product = Product::find($auction->product_id);
        expect($product->status)->toBe(ProductStatus::Published);
    });

    test('seller cannot bid on own auction', function () {
        $auctionId = createAuctionFor($this, $this->users['alice'], $this->category, [
            'title' => 'Seller Item',
            'starting_price' => 500,
            'min_bid_increment' => 25,
            'reserve_price' => 600,
        ]);

        Sanctum::actingAs($this->users['alice']);
        $response = $this->postJson("/api/v1/auctions/{$auctionId}/bids", ['amount' => 600]);

        $response->assertStatus(403);
    });

    test('search and filter products', function () {
        $seller = $this->users['alice'];

        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $this->category->id,
            'title' => 'iPhone 15',
            'slug' => 'iphone-15',
            'starting_price' => 500,
            'status' => ProductStatus::Published->value,
        ]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $this->category->id,
            'title' => 'MacBook Pro',
            'slug' => 'macbook-pro',
            'starting_price' => 1200,
            'status' => ProductStatus::Published->value,
        ]);
        Product::factory()->create([
            'seller_id' => $seller->id,
            'category_id' => $this->category->id,
            'title' => 'Vintage Watch',
            'slug' => 'vintage-watch',
            'starting_price' => 300,
            'status' => ProductStatus::Published->value,
        ]);

        $response = $this->getJson('/api/v1/search/products?q=iPhone');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.title'))->toBe('iPhone 15');

        $response = $this->getJson("/api/v1/search/products?category={$this->category->slug}");
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(3);

        $response = $this->getJson('/api/v1/search/products?max_price=600');
        $response->assertStatus(200);

        $response = $this->getJson('/api/v1/search/products?min_price=400');
        $response->assertStatus(200);
    });
});
