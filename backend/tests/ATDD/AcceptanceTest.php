<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Notification;
use App\Enums\UserRole;
use App\Enums\ProductStatus;
use App\Enums\ProductCondition;
use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

beforeEach(function () {
    $this->users = [];
    $this->users['alice'] = User::factory()->create([
        'name' => 'Alice Seller',
        'email' => 'alice@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::Seller->value,
    ]);

    $this->users['bob'] = User::factory()->create([
        'name' => 'Bob Bidder',
        'email' => 'bob@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::User->value,
    ]);

    $this->users['charlie'] = User::factory()->create([
        'name' => 'Charlie Bidder',
        'email' => 'charlie@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::User->value,
    ]);

    $this->category = Category::factory()->create([
        'name' => 'Electronics',
        'slug' => 'electronics',
        'is_active' => true,
    ]);
});

describe('Complete Auction Lifecycle - ATDD', function () {
    test('complete successful auction from listing to payment', function () {
        // Seller creates product
        Sanctum::actingAs($this->users['alice']);

        $productResponse = $this->postJson('/api/products', [
            'title' => 'iPhone 15 Pro',
            'description' => 'Brand new iPhone',
            'category_id' => $this->category->id,
            'condition' => ProductCondition::New->value,
            'starting_price' => 500,
            'reserve_price' => 800,
            'currency' => 'USD',
        ]);

        $productResponse->assertStatus(201);
        $productId = $productResponse->json('data.id');

        // Publish product
        $publishResponse = $this->postJson("/api/products/{$productId}/publish");
        $publishResponse->assertStatus(200);

        // Create auction
        $auctionResponse = $this->postJson('/api/auctions', [
            'product_id' => $productId,
            'starting_price' => 500,
            'min_bid_increment' => 25,
            'reserve_price' => 800,
            'start_date' => Carbon::now()->subMinutes(5)->toISOString(),
            'end_date' => Carbon::now()->addMinutes(10)->toISOString(),
        ]);

        $auctionResponse->assertStatus(201);
        $auctionId = $auctionResponse->json('data.id');

        // Bob places bid
        Sanctum::actingAs($this->users['bob']);
        $bid1 = $this->postJson("/api/auctions/{$auctionId}/bids", ['amount' => 550]);
        $bid1->assertStatus(201);

        // Alice (as another bidder) places bid
        Sanctum::actingAs($this->users['alice']);
        $bid2 = $this->postJson("/api/auctions/{$auctionId}/bids", ['amount' => 575]);
        $bid2->assertStatus(201);

        // Bob places higher bid
        Sanctum::actingAs($this->users['bob']);
        $bid3 = $this->postJson("/api/auctions/{$auctionId}/bids", ['amount' => 600]);
        $bid3->assertStatus(201);

        // Simulate auction end by updating end_date to past
        $auction = Auction::find($auctionId);
        $auction->update(['end_date' => Carbon::now()->subMinute()]);

        // Run auction completion job
        $this->artisan('auctions:complete')->run();

        // Verify results
        $auction->refresh();
        expect($auction->status)->toBe(AuctionStatus::Completed->value);
        expect($auction->winner_id)->toBe($this->users['bob']->id);
        expect($auction->current_price)->toBe(600.00);

        // Check notifications
        $bobNotifications = Notification::where('user_id', $this->users['bob']->id)->get();
        $aliceNotifications = Notification::where('user_id', $this->users['alice']->id)->get();

        expect($bobNotifications->where('type', 'auction_won')->count())->toBe(1);
        expect($aliceNotifications->where('type', 'auction_completed')->count())->toBe(1);
    });

    test('auction ends unsold when reserve not met', function () {
        Sanctum::actingAs($this->users['alice']);

        $productResponse = $this->postJson('/api/products', [
            'title' => 'Rare Coin',
            'category_id' => $this->category->id,
            'condition' => ProductCondition::Used->value,
            'starting_price' => 100,
            'reserve_price' => 500,
        ]);

        $productId = $productResponse->json('data.id');

        $this->postJson("/api/products/{$productId}/publish")->assertStatus(200);

        $auctionResponse = $this->postJson('/api/auctions', [
            'product_id' => $productId,
            'starting_price' => 100,
            'min_bid_increment' => 10,
            'reserve_price' => 500,
            'start_date' => Carbon::now()->subMinutes(5)->toISOString(),
            'end_date' => Carbon::now()->addMinutes(10)->toISOString(),
        ]);

        $auctionId = $auctionResponse->json('data.id');

        Sanctum::actingAs($this->users['bob']);
        $this->postJson("/api/auctions/{$auctionId}/bids", ['amount' => 200])->assertStatus(201);

        $auction = Auction::find($auctionId);
        $auction->update(['end_date' => Carbon::now()->subMinute()]);

        $this->artisan('auctions:complete')->run();

        $auction->refresh();
        expect($auction->status)->toBe(AuctionStatus::Unsold->value);
        expect($auction->winner_id)->toBeNull();

        $aliceNotifications = Notification::where('user_id', $this->users['alice']->id)->get();
        expect($aliceNotifications->where('type', 'auction_unsold')->count())->toBe(1);
    });

    test('auto-extend works when bid placed near end', function () {
        Sanctum::actingAs($this->users['alice']);

        $productResponse = $this->postJson('/api/products', [
            'title' => 'Auto-extend Item',
            'category_id' => $this->category->id,
            'condition' => ProductCondition::New->value,
            'starting_price' => 500,
            'reserve_price' => 600,
        ]);

        $productId = $productResponse->json('data.id');
        $this->postJson("/api/products/{$productId}/publish")->assertStatus(200);

        $auctionResponse = $this->postJson('/api/auctions', [
            'product_id' => $productId,
            'starting_price' => 500,
            'min_bid_increment' => 25,
            'reserve_price' => 600,
            'start_date' => Carbon::now()->subMinutes(5)->toISOString(),
            'end_date' => Carbon::now()->addSeconds(30)->toISOString(),
        ]);

        $auctionId = $auctionResponse->json('data.id');
        $originalEndDate = Auction::find($auctionId)->end_date;

        // Bob places bid at 20 seconds before end (within 60 second threshold)
        Sanctum::actingAs($this->users['bob']);
        $this->postJson("/api/auctions/{$auctionId}/bids", ['amount' => 525])->assertStatus(201);

        $auction = Auction::find($auctionId)->refresh();
        expect($auction->end_date->gt($originalEndDate))->toBeTrue();

        // Charlie places bid
        Sanctum::actingAs($this->users['charlie']);
        $this->postJson("/api/auctions/{$auctionId}/bids", ['amount' => 550])->assertStatus(201);

        $auction->update(['end_date' => Carbon::now()->subMinute()]);
        $this->artisan('auctions:complete')->run();

        $auction->refresh();
        expect($auction->winner_id)->toBe($this->users['charlie']->id);
        expect($auction->current_price)->toBe(550.00);
    });

    test('seller cannot bid on own auction', function () {
        Sanctum::actingAs($this->users['alice']);

        $productResponse = $this->postJson('/api/products', [
            'title' => 'Seller Item',
            'category_id' => $this->category->id,
            'condition' => ProductCondition::New->value,
            'starting_price' => 500,
            'reserve_price' => 600,
        ]);

        $productId = $productResponse->json('data.id');
        $this->postJson("/api/products/{$productId}/publish")->assertStatus(200);

        $auctionResponse = $this->postJson('/api/auctions', [
            'product_id' => $productId,
            'starting_price' => 500,
            'min_bid_increment' => 25,
            'reserve_price' => 600,
            'start_date' => Carbon::now()->subMinutes(5)->toISOString(),
            'end_date' => Carbon::now()->addMinutes(10)->toISOString(),
        ]);

        $auctionId = $auctionResponse->json('data.id');

        // Seller tries to bid on own auction
        $response = $this->postJson("/api/auctions/{$auctionId}/bids", ['amount' => 600]);
        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Cannot bid on your own auction']);
    });

    test('watchlist functionality', function () {
        Sanctum::actingAs($this->users['alice']);

        $productResponse = $this->postJson('/api/products', [
            'title' => 'Watched Item',
            'category_id' => $this->category->id,
            'condition' => ProductCondition::New->value,
            'starting_price' => 500,
            'reserve_price' => 600,
        ]);

        $productId = $productResponse->json('data.id');
        $this->postJson("/api/products/{$productId}/publish")->assertStatus(200);

        $auctionResponse = $this->postJson('/api/auctions', [
            'product_id' => $productId,
            'starting_price' => 500,
            'min_bid_increment' => 25,
            'reserve_price' => 600,
            'start_date' => Carbon::now()->subMinutes(5)->toISOString(),
            'end_date' => Carbon::now()->addMinutes(10)->toISOString(),
        ]);

        $auctionId = $auctionResponse->json('data.id');

        // Bob adds to watchlist
        Sanctum::actingAs($this->users['bob']);
        $watchResponse = $this->postJson("/api/auctions/{$auctionId}/watch");
        $watchResponse->assertStatus(200);

        // Verify watcher added
        $auction = Auction::find($auctionId);
        expect($auction->participants()->where('user_id', $this->users['bob']->id)->where('is_watcher', true)->exists())->toBeTrue();

        // Another bidder places bid - Bob should get notification
        Sanctum::actingAs($this->users['charlie']);
        $this->postJson("/api/auctions/{$auctionId}/bids", ['amount' => 525])->assertStatus(201);

        $bobNotifications = Notification::where('user_id', $this->users['bob']->id)->get();
        expect($bobNotifications->where('type', 'new_bid')->count())->toBe(1);

        // Bob removes from watchlist
        $unwatchResponse = $this->deleteJson("/api/auctions/{$auctionId}/watch");
        $unwatchResponse->assertStatus(200);

        expect($auction->fresh()->participants()->where('user_id', $this->users['bob']->id)->where('is_watcher', true)->exists())->toBeFalse();
    });

    test('search and filter auctions', function () {
        // Create multiple auctions
        $auctions = [];
        foreach (['iPhone 15', 'MacBook Pro', 'Vintage Watch'] as $title) {
            Sanctum::actingAs($this->users['alice']);
            $product = Product::factory()->create([
                'seller_id' => $this->users['alice']->id,
                'category_id' => $this->category->id,
                'title' => $title,
                'slug' => str()->slug($title),
                'status' => ProductStatus::InAuction->value,
            ]);

            $auction = Auction::factory()->create([
                'product_id' => $product->id,
                'seller_id' => $this->users['alice']->id,
                'status' => AuctionStatus::Active->value,
            ]);
            $auctions[] = $auction;
        }

        // Search for iPhone
        $response = $this->getJson('/api/search/products?q=iPhone');
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.title'))->toBe('iPhone 15');

        // Filter by category
        $response = $this->getJson("/api/search/products?category={$this->category->slug}");
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(2);

        // Filter by max price
        $response = $this->getJson('/api/search/products?max_price=600');
        $response->assertStatus(200);
        // iPhone 15 and Vintage Watch should be under 600
        expect($response->json('data'))->toHaveCount(2);
    });
});