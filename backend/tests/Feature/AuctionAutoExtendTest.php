<?php

use App\Enums\AuctionStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Modules\Auctions\Services\AuctionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

uses(LaravelTestCase::class);

beforeEach(function () {
    cleanTestDatabase();
    seedTestRoles();

    $this->seller = User::factory()->create([
        'email' => 'seller@example.com',
        'role' => UserRole::Seller->value,
        'is_active' => true,
    ]);

    $this->category = Category::factory()->create(['slug' => 'electronics']);

    $this->product = Product::factory()->create([
        'seller_id' => $this->seller->id,
        'category_id' => $this->category->id,
        'status' => ProductStatus::Published->value,
    ]);

    $this->service = app(AuctionService::class);
});

test('autoExtend extends an auction ending within the threshold', function () {
    $auction = Auction::factory()->create([
        'product_id' => $this->product->id,
        'seller_id' => $this->seller->id,
        'status' => AuctionStatus::Active,
        'end_date' => Carbon::now()->addSeconds(30),
    ]);

    $result = $this->service->autoExtend($auction);

    expect($result)->not->toBeNull();
    expect($result->end_date->greaterThan(Carbon::now()->addMinutes(4)))->toBeTrue();
});

test('autoExtend does not extend an auction ending far in the future', function () {
    $auction = Auction::factory()->create([
        'product_id' => $this->product->id,
        'seller_id' => $this->seller->id,
        'status' => AuctionStatus::Active,
        'end_date' => Carbon::now()->addHours(2),
    ]);

    expect($this->service->autoExtend($auction))->toBeNull();
});

test('autoExtend ignores a non-active auction', function () {
    $auction = Auction::factory()->create([
        'product_id' => $this->product->id,
        'seller_id' => $this->seller->id,
        'status' => AuctionStatus::Scheduled,
        'end_date' => Carbon::now()->addSeconds(30),
    ]);

    expect($this->service->autoExtend($auction))->toBeNull();
});