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
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;
use Illuminate\Support\Facades\DB;

uses(LaravelTestCase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'role' => UserRole::User->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
    DB::connection('mongodb_testing')->table('roles')->where('name', 'user')->value('id');
    DB::connection('mongodb_testing')->table('model_has_roles')->insert([
        'model_id' => $this->user->getKey(),
        'model_type' => User::class,
        'role_id' => DB::connection('mongodb_testing')->table('roles')->where('name', 'user')->value('id'),
    ]);

    $this->seller = User::factory()->create([
        'name' => 'Seller User',
        'email' => 'seller@example.com',
        'password' => 'password123',
        'role' => UserRole::Seller->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
    DB::connection('mongodb_testing')->table('model_has_roles')->insert([
        'model_id' => $this->seller->getKey(),
        'model_type' => User::class,
        'role_id' => DB::connection('mongodb_testing')->table('roles')->where('name', 'seller')->value('id'),
    ]);

    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => UserRole::Admin->value,
        'is_active' => true,
        'email_verified_at' => Carbon::now(),
    ]);
    DB::connection('mongodb_testing')->table('model_has_roles')->insert([
        'model_id' => $this->admin->getKey(),
        'model_type' => User::class,
        'role_id' => DB::connection('mongodb_testing')->table('roles')->where('name', 'admin')->value('id'),
    ]);

    $this->category = Category::factory()->create([
        'name' => 'Electronics',
        'slug' => 'electronics',
        'is_active' => true,
    ]);
});

afterEach(function () {
    $testEmails = ['test@example.com', 'seller@example.com', 'admin@example.com', 'new@example.com'];
    $userIds = DB::connection('mongodb_testing')->table('users')->whereIn('email', $testEmails)->pluck('_id')->toArray();

    if (!empty($userIds)) {
        DB::connection('mongodb_testing')->table('users')->whereIn('email', $testEmails)->delete();
        DB::connection('mongodb_testing')->table('personal_access_tokens')->whereIn('tokenable_id', $userIds)->delete();
        DB::connection('mongodb_testing')->table('model_has_roles')->whereIn('model_id', $userIds)->delete();
    }

    DB::connection('mongodb_testing')->table('products')->whereIn('seller_id', $userIds)->delete();
    DB::connection('mongodb_testing')->table('auctions')->whereIn('seller_id', $userIds)->delete();
    DB::connection('mongodb_testing')->table('bids')->whereIn('user_id', $userIds)->delete();
    DB::connection('mongodb_testing')->table('categories')->where('slug', 'electronics')->delete();
});

describe('Authentication API', function () {
    test('user can register', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ]);

        expect(User::where('email', 'new@example.com')->exists())->toBeTrue();
    });

    test('user can login', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);
    });

    test('login fails with wrong password', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    });

    test('authenticated user can logout', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
    });

    test('authenticated user can get profile', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => 'test@example.com']);
    });
});

describe('Category API', function () {
    test('anyone can list categories', function () {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'slug']]]);
    });

    test('anyone can get category with products', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Published->value,
        ]);

        $response = $this->getJson("/api/v1/categories/{$this->category->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Electronics']);
    });

    test('admin can create category', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/admin/categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
            'description' => 'Description',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Category']);

        expect(Category::where('slug', 'new-category')->exists())->toBeTrue();
    });

    test('non-admin cannot create category', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/admin/categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
        ]);

        $response->assertStatus(403);
    });

    test('admin can update category', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/v1/admin/categories/{$this->category->id}", [
            'name' => 'Updated Category',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Category']);
    });

    test('admin can delete category', function () {
        Sanctum::actingAs($this->admin);

        $category = Category::factory()->create(['name' => 'To Delete', 'slug' => 'to-delete']);

        $response = $this->deleteJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(204);
        expect(Category::where('id', $category->id)->exists())->toBeFalse();
    });
});

describe('Product API', function () {
    test('anyone can list published products', function () {
        Product::factory()->count(3)->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Published->value,
        ]);

        Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Draft->value,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(3, 'data');
    });

    test('anyone can get product details', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Published->value,
        ]);

        $response = $this->getJson("/api/v1/products/{$product->slug}");

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => $product->title]);
    });

    test('seller can create product', function () {
        Sanctum::actingAs($this->seller);

        $response = $this->postJson('/api/v1/products', [
            'title' => 'My Product',
            'description' => 'Product description',
            'category_id' => $this->category->id,
            'condition' => ProductCondition::New->value,
            'starting_price' => 100.00,
            'reserve_price' => 150.00,
            'currency' => 'USD',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'My Product']);

        expect(Product::where('title', 'My Product')->exists())->toBeTrue();
    });

    test('non-seller cannot create product', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/products', [
            'title' => 'My Product',
            'category_id' => $this->category->id,
        ]);

        $response->assertStatus(403);
    });

    test('seller can update own product', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Draft->value,
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);
    });

    test('seller cannot update other seller product', function () {
        $otherSeller = User::factory()->create(['role' => UserRole::Seller->value]);
        $product = Product::factory()->create([
            'seller_id' => $otherSeller->id,
            'category_id' => $this->category->id,
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    });

    test('seller can delete own draft product', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Draft->value,
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(204);
    });

    test('seller cannot delete published product', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Published->value,
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(422);
    });
});

describe('Auction API', function () {
    test('anyone can list active auctions', function () {
        $auction = Auction::factory()->create([
            'product_id' => Product::factory()->create([
                'seller_id' => $this->seller->id,
                'category_id' => $this->category->id,
                'status' => ProductStatus::InAuction->value,
            ])->id,
            'seller_id' => $this->seller->id,
            'start_date' => Carbon::now()->subHour(),
            'end_date' => Carbon::now()->addHour(),
            'status' => AuctionStatus::Active->value,
        ]);

        Auction::factory()->create([
            'status' => AuctionStatus::Scheduled->value,
            'start_date' => Carbon::now()->addHour(),
        ]);

        $response = $this->getJson('/api/v1/auctions');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    });

    test('anyone can get auction details', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::InAuction->value,
        ]);

        $auction = Auction::factory()->create([
            'product_id' => $product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Active->value,
        ]);

        $response = $this->getJson("/api/v1/auctions/{$auction->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => (string) $auction->id]);
    });

    test('seller can create auction for own product', function () {
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

    test('seller cannot create auction for other product', function () {
        $otherSeller = User::factory()->create(['role' => UserRole::Seller->value]);
        $product = Product::factory()->create([
            'seller_id' => $otherSeller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Published->value,
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->postJson('/api/v1/auctions', [
            'product_id' => $product->id,
            'starting_price' => 100.00,
            'min_bid_increment' => 10.00,
            'start_date' => Carbon::now()->addMinutes(5)->toISOString(),
            'end_date' => Carbon::now()->addHours(24)->toISOString(),
        ]);

        $response->assertStatus(403);
    });

    test('seller can cancel own scheduled auction', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::Published->value,
        ]);

        $auction = Auction::factory()->create([
            'product_id' => $product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Scheduled->value,
            'start_date' => Carbon::now()->addHour(),
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/cancel");

        $response->assertStatus(200);
        expect(Auction::where('id', $auction->id)->where('status', AuctionStatus::Cancelled->value)->exists())->toBeTrue();
    });

    test('cannot cancel active auction', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::InAuction->value,
        ]);

        $auction = Auction::factory()->create([
            'product_id' => $product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Active->value,
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/cancel");

        $response->assertStatus(422);
    });
});

describe('Bid API', function () {
    test('authenticated user can place bid on active auction', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::InAuction->value,
        ]);

        $auction = Auction::factory()->create([
            'product_id' => $product->id,
            'seller_id' => $this->seller->id,
            'starting_price' => 100.00,
            'current_price' => 100.00,
            'min_bid_increment' => 10.00,
            'status' => AuctionStatus::Active->value,
            'start_date' => Carbon::now()->subHour(),
            'end_date' => Carbon::now()->addHour(),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/bids", [
            'amount' => 120.00,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['amount' => 120.00]);

        expect(Bid::where('auction_id', $auction->id)->where('amount', 120.00)->exists())->toBeTrue();
    });

    test('bid below minimum increment is rejected', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::InAuction->value,
        ]);

        $auction = Auction::factory()->create([
            'product_id' => $product->id,
            'seller_id' => $this->seller->id,
            'current_price' => 100.00,
            'min_bid_increment' => 10.00,
            'status' => AuctionStatus::Active->value,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/bids", [
            'amount' => 105.00,
        ]);

        $response->assertStatus(422);
    });

    test('user cannot bid on own auction', function () {
        $product = Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'status' => ProductStatus::InAuction->value,
        ]);

        $auction = Auction::factory()->create([
            'product_id' => $product->id,
            'seller_id' => $this->seller->id,
            'status' => AuctionStatus::Active->value,
        ]);

        Sanctum::actingAs($this->seller);

        $response = $this->postJson("/api/v1/auctions/{$auction->id}/bids", [
            'amount' => 120.00,
        ]);

        $response->assertStatus(403);
    });

    test('user can get their bids', function () {
        $auction = Auction::factory()->create(['status' => AuctionStatus::Active->value]);
        Bid::factory()->count(3)->create(['user_id' => $this->user->id, 'auction_id' => $auction->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/my-bids');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    });
});

describe('Notification API', function () {
    test('user can get their notifications', function () {
        Notification::factory()->count(5)->create(['user_id' => $this->user->id]);
        Notification::factory()->create(['user_id' => $this->seller->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    });

    test('user can mark notification as read', function () {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        expect(Notification::where('id', $notification->id)->where('read_at', '!=', null)->exists())->toBeTrue();
    });

    test('user can mark all notifications as read', function () {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id, 'read_at' => null]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/notifications/read-all');

        $response->assertStatus(200);
        expect(Notification::where('user_id', $this->user->id)->unread()->count())->toBe(0);
    });
});

describe('Search API', function () {
    test('anyone can search products', function () {
        Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'iPhone 15 Pro',
            'status' => ProductStatus::Published->value,
        ]);

        Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Samsung Galaxy',
            'status' => ProductStatus::Published->value,
        ]);

        $response = $this->getJson('/api/v1/search/products?q=iPhone');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'iPhone 15 Pro']);
    });

    test('search supports category filter', function () {
        $cat1 = Category::factory()->create(['name' => 'Phones', 'slug' => 'phones']);
        $cat2 = Category::factory()->create(['name' => 'Laptops', 'slug' => 'laptops']);

        Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $cat1->id,
            'title' => 'iPhone',
            'status' => ProductStatus::Published->value,
        ]);

        Product::factory()->create([
            'seller_id' => $this->seller->id,
            'category_id' => $cat2->id,
            'title' => 'MacBook',
            'status' => ProductStatus::Published->value,
        ]);

        $response = $this->getJson("/api/v1/search/products?category={$cat1->slug}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'iPhone']);
    });
});
