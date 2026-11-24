<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Auction;
use App\Enums\UserRole;
use App\Enums\ProductStatus;
use App\Enums\AuctionStatus;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

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
    $sellerRoleId = \DB::connection('mongodb_testing')->table('roles')->where('name', 'seller')->value('id');
    \DB::connection('mongodb_testing')->table('model_has_roles')->insert([
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

test('seller can create auction for own product - new test', function () {
    die("NEW TEST REACHED");
    
    $product = Product::factory()->create([
        'seller_id' => $this->seller->id,
        'category_id' => $this->category->id,
        'status' => ProductStatus::Published->value,
    ]);

    Sanctum::actingAs($this->seller);

    $response = $this->postJson('/api/v1/auctions', [
        'product_id' => $product->id,
        'starting_price' => 100.00,
        'min_bid_increment' => 10.00,
        'reserve_price' => 150.00,
        'start_date' => Carbon::now()->addMinutes(5)->toISOString(),
        'end_date' => Carbon::now()->addHours(24)->toISOString(),
    ]);

    $response->assertStatus(201);

    expect(Auction::where('product_id', $product->id)->exists())->toBeTrue();
});