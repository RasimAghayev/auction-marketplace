<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\AuctionParticipant;
use App\Models\AuctionHistory;
use App\Models\Notification;
use App\Enums\UserRole;
use App\Enums\ProductStatus;
use App\Enums\ProductCondition;
use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use App\Enums\AuctionEventType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\TestCase as LaravelTestCase;

uses(LaravelTestCase::class);

beforeEach(function () {
    // Clean all domain collections so each test starts from a known state.
    foreach ([
        'products', 'auctions', 'bids', 'notifications', 'categories',
        'activity_logs', 'auction_participants', 'auction_history',
        'personal_access_tokens', 'model_has_roles', 'model_has_permissions', 'users',
    ] as $collection) {
        DB::connection('mongodb_testing')->table($collection)->delete();
    }

    // Models use getConnectionName() to switch to mongodb_testing in testing env
    $this->user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'role' => UserRole::User->value,
        'is_active' => true,
    ]);

    $this->seller = User::factory()->create([
        'name' => 'Seller User',
        'email' => 'seller@example.com',
        'password' => 'password123',
        'role' => UserRole::Seller->value,
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => UserRole::Admin->value,
        'is_active' => true,
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
        'description' => 'Test description',
        'condition' => ProductCondition::New->value,
        'starting_price' => 100.00,
        'reserve_price' => 150.00,
        'currency' => 'USD',
        'status' => ProductStatus::Published->value,
    ]);

    $this->auction = Auction::factory()->create([
        'product_id' => $this->product->id,
        'seller_id' => $this->seller->id,
        'starting_price' => 100.00,
        'current_price' => 100.00,
        'min_bid_increment' => 10.00,
        'reserve_price' => 150.00,
        'start_date' => Carbon::now()->subHour(),
        'end_date' => Carbon::now()->addHour(),
        'status' => AuctionStatus::Active->value,
    ]);
});

describe('User Model', function () {
    test('can create user with factory', function () {
        expect($this->user)->toBeInstanceOf(User::class)
            ->and($this->user->name)->toBe('Test User')
            ->and($this->user->email)->toBe('test@example.com')
            ->and($this->user->is_active)->toBeTrue();
    });

    test('user roles work correctly', function () {
        expect($this->user->isAdmin())->toBeFalse()
            ->and($this->user->isSeller())->toBeFalse()
            ->and($this->seller->isAdmin())->toBeFalse()
            ->and($this->seller->isSeller())->toBeTrue()
            ->and($this->admin->isAdmin())->toBeTrue()
            ->and($this->admin->isSeller())->toBeFalse();
    });

    test('user can create products if seller or admin', function () {
        expect($this->user->canCreateProducts())->toBeFalse()
            ->and($this->seller->canCreateProducts())->toBeTrue()
            ->and($this->admin->canCreateProducts())->toBeTrue();
    });

    test('user can create auctions if seller or admin', function () {
        expect($this->user->canCreateAuctions())->toBeFalse()
            ->and($this->seller->canCreateAuctions())->toBeTrue()
            ->and($this->admin->canCreateAuctions())->toBeTrue();
    });

    test('password is hashed', function () {
        expect($this->user->password)->not()->toBe('password123');
    });

    test('user relationships work', function () {
        $seller = User::factory()->create(['role' => UserRole::Seller->value]);
        $buyer = User::factory()->create(['role' => UserRole::User->value]);

        $product = Product::factory()->create(['seller_id' => $seller->id]);
        $auction = Auction::factory()->create(['seller_id' => $seller->id]);
        $bid = Bid::factory()->create(['user_id' => $buyer->id]);

        expect($seller->products()->count())->toBe(1)
            ->and($seller->auctions()->count())->toBe(1)
            ->and($buyer->bids()->count())->toBe(1);
    });
});

describe('Product Model', function () {
    test('can create product with factory', function () {
        expect($this->product)->toBeInstanceOf(Product::class)
            ->and($this->product->title)->toBe('Test Product')
            ->and($this->product->slug)->toBe('test-product')
            ->and($this->product->status)->toBe(ProductStatus::Published);
    });

    test('product belongs to seller', function () {
        expect($this->product->seller->id)->toBe($this->seller->id);
    });

    test('product belongs to category', function () {
        expect($this->product->category->id)->toBe($this->category->id);
    });

    test('product can have auction', function () {
        expect($this->product->auction->id)->toBe($this->auction->id);
    });

    test('primary image returns first image', function () {
        $product = Product::factory()->create([
            'images' => [
                ['url' => 'image1.jpg', 'is_primary' => false],
                ['url' => 'image2.jpg', 'is_primary' => true],
            ]
        ]);

        expect($product->primaryImage()['url'])->toBe('image2.jpg');
    });

    test('primary image returns first if no primary set', function () {
        $product = Product::factory()->create([
            'images' => [
                ['url' => 'image1.jpg', 'is_primary' => false],
                ['url' => 'image2.jpg', 'is_primary' => false],
            ]
        ]);

        expect($product->primaryImage()['url'])->toBe('image1.jpg');
    });

    test('scopes work correctly', function () {
        Product::factory()->create(['status' => ProductStatus::Draft->value]);
        Product::factory()->create(['status' => ProductStatus::InAuction->value]);

        expect(Product::published()->count())->toBe(1)
            ->and(Product::inAuction()->count())->toBe(1)
            ->and(Product::availableForAuction()->count())->toBe(2);
    });

    test('canBeAuctioned returns correct values', function () {
        $published = Product::factory()->create(['status' => ProductStatus::Published->value]);
        $draft = Product::factory()->create(['status' => ProductStatus::Draft->value]);
        $inAuction = Product::factory()->create(['status' => ProductStatus::InAuction->value]);
        $sold = Product::factory()->create(['status' => ProductStatus::Sold->value]);

        expect($published->canBeAuctioned())->toBeTrue()
            ->and($draft->canBeAuctioned())->toBeTrue()
            ->and($inAuction->canBeAuctioned())->toBeFalse()
            ->and($sold->canBeAuctioned())->toBeFalse();
    });

    test('route key name is slug', function () {
        expect($this->product->getRouteKeyName())->toBe('slug');
    });
});

describe('Category Model', function () {
    test('can create category with factory', function () {
        expect($this->category)->toBeInstanceOf(Category::class)
            ->and($this->category->name)->toBe('Electronics')
            ->and($this->category->slug)->toBe('electronics')
            ->and($this->category->is_active)->toBeTrue();
    });

    test('category hierarchy works', function () {
        $parent = Category::factory()->create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::factory()->create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

        expect($child->parent->id)->toBe($parent->id)
            ->and($parent->children()->count())->toBe(1)
            ->and($parent->activeChildren()->count())->toBe(1);
    });

    test('scopes work correctly', function () {
        Category::factory()->create(['is_active' => false, 'parent_id' => $this->category->id]);

        expect(Category::active()->count())->toBe(1)
            ->and(Category::root()->count())->toBe(1);
    });
});

describe('Auction Model', function () {
    test('can create auction with factory', function () {
        expect($this->auction)->toBeInstanceOf(Auction::class)
            ->and($this->auction->starting_price)->toEqual(100.00)
            ->and($this->auction->current_price)->toEqual(100.00)
            ->and($this->auction->status)->toBe(AuctionStatus::Active);
    });

    test('auction relationships work', function () {
        expect($this->auction->product->id)->toBe($this->product->id)
            ->and($this->auction->seller->id)->toBe($this->seller->id);
    });

    test('auction status helpers work', function () {
        expect($this->auction->isActive())->toBeTrue()
            ->and($this->auction->isScheduled())->toBeFalse()
            ->and($this->auction->isFinal())->toBeFalse()
            ->and($this->auction->hasEnded())->toBeFalse()
            ->and($this->auction->hasStarted())->toBeTrue();
    });

    test('auction auto-extend threshold', function () {
        $auction = Auction::factory()->create([
            'end_date' => Carbon::now()->addSeconds(30),
        ]);

        expect($auction->isWithinAutoExtendThreshold())->toBeTrue();
    });

    test('getRemainingTimeAttribute returns correct value', function () {
        $auction = Auction::factory()->create([
            'end_date' => Carbon::now()->addMinutes(10),
        ]);

        expect($auction->remaining_time)->toBeGreaterThan(500)
            ->and($auction->remaining_time)->toBeLessThan(700);
    });

    test('getNextMinimumBidAttribute calculates correctly', function () {
        expect($this->auction->next_minimum_bid)->toBe(110.00);
    });

    test('scopes work correctly', function () {
        Auction::factory()->create(['status' => AuctionStatus::Scheduled->value, 'start_date' => Carbon::now()->addHour()]);
        Auction::factory()->create(['status' => AuctionStatus::Completed->value]);

        expect(Auction::active()->count())->toBe(1)
            ->and(Auction::scheduled()->count())->toBe(1)
            ->and(Auction::ended()->count())->toBe(1)
            ->and(Auction::upcoming()->count())->toBe(1)
            ->and(Auction::live()->count())->toBe(1);
    });
});

describe('Bid Model', function () {
    test('can create bid with factory', function () {
        $bid = Bid::factory()->create([
            'auction_id' => $this->auction->id,
            'user_id' => $this->user->id,
            'amount' => 120.00,
            'status' => BidStatus::Accepted->value,
        ]);

        expect($bid)->toBeInstanceOf(Bid::class)
            ->and($bid->amount)->toEqual(120.00)
            ->and($bid->status)->toBe(BidStatus::Accepted);
    });

    test('bid relationships work', function () {
        $bid = Bid::factory()->create([
            'auction_id' => $this->auction->id,
            'user_id' => $this->user->id,
        ]);

        expect($bid->auction->id)->toBe($this->auction->id)
            ->and($bid->user->id)->toBe($this->user->id);
    });

    test('bid status helpers work', function () {
        $winningBid = Bid::factory()->create(['status' => BidStatus::Winning->value]);
        $acceptedBid = Bid::factory()->create(['status' => BidStatus::Accepted->value]);
        $pendingBid = Bid::factory()->create(['status' => BidStatus::Pending->value]);
        $rejectedBid = Bid::factory()->create(['status' => BidStatus::Rejected->value]);

        expect($winningBid->isWinning())->toBeTrue()
            ->and($acceptedBid->isActive())->toBeTrue()
            ->and($pendingBid->isActive())->toBeTrue()
            ->and($rejectedBid->isFinal())->toBeTrue();
    });

    test('scopes work correctly', function () {
        Bid::factory()->create(['auction_id' => $this->auction->id, 'status' => BidStatus::Winning->value]);
        Bid::factory()->create(['auction_id' => $this->auction->id, 'status' => BidStatus::Rejected->value]);
        Bid::factory()->create(['user_id' => $this->user->id, 'status' => BidStatus::Accepted->value]);

        expect(Bid::forAuction($this->auction->id)->count())->toBe(2)
            ->and(Bid::byUser($this->user->id)->count())->toBe(1)
            ->and(Bid::winning()->count())->toBe(1)
            ->and(Bid::active()->count())->toBe(2);
    });
});

describe('AuctionParticipant Model', function () {
    test('can create participant with factory', function () {
        $participant = AuctionParticipant::factory()->create([
            'auction_id' => $this->auction->id,
            'user_id' => $this->user->id,
            'total_bids' => 5,
            'highest_bid' => 200.00,
            'is_watcher' => false,
        ]);

        expect($participant)->toBeInstanceOf(AuctionParticipant::class)
            ->and($participant->total_bids)->toBe(5)
            ->and($participant->highest_bid)->toEqual(200.00)
            ->and($participant->is_watcher)->toBeFalse();
    });

    test('participant relationships work', function () {
        $participant = AuctionParticipant::factory()->create([
            'auction_id' => $this->auction->id,
            'user_id' => $this->user->id,
        ]);

        expect($participant->auction->id)->toBe($this->auction->id)
            ->and($participant->user->id)->toBe($this->user->id);
    });
});

describe('AuctionHistory Model', function () {
    test('can create history with factory', function () {
        $history = AuctionHistory::factory()->create([
            'auction_id' => $this->auction->id,
            'user_id' => $this->user->id,
            'event_type' => AuctionEventType::BidPlaced->value,
            'data' => ['amount' => 150.00, 'bid_id' => 'bid-123'],
        ]);

        expect($history)->toBeInstanceOf(AuctionHistory::class)
            ->and($history->event_type)->toBe(AuctionEventType::BidPlaced)
            ->and($history->data['amount'])->toEqual(150.00);
    });

    test('history relationships work', function () {
        $history = AuctionHistory::factory()->create([
            'auction_id' => $this->auction->id,
            'user_id' => $this->user->id,
        ]);

        expect($history->auction->id)->toBe($this->auction->id)
            ->and($history->user->id)->toBe($this->user->id);
    });
});

describe('Notification Model', function () {
    test('can create notification with factory', function () {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'bid_placed',
            'title' => 'New Bid',
            'message' => 'Someone placed a bid on your auction',
            'data' => ['auction_id' => $this->auction->id],
        ]);

        expect($notification)->toBeInstanceOf(Notification::class)
            ->and($notification->type)->toBe('bid_placed')
            ->and($notification->isRead())->toBeFalse();
    });

    test('markAsRead works', function () {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null,
        ]);

        expect($notification->isRead())->toBeFalse();

        $notification->markAsRead();

        expect($notification->fresh()->isRead())->toBeTrue()
            ->and($notification->fresh()->read_at)->not()->toBeNull();
    });

    test('scopes work correctly', function () {
        Notification::factory()->create(['user_id' => $this->user->id, 'read_at' => Carbon::now()]);
        Notification::factory()->create(['user_id' => $this->user->id, 'read_at' => null]);

        expect(Notification::unread()->count())->toBe(1)
            ->and(Notification::read()->count())->toBe(1);
    });
});

describe('ActivityLog Model', function () {
    test('can create activity log with factory', function () {
        $log = \App\Models\ActivityLog::factory()->create([
            'log_name' => 'test',
            'description' => 'Test activity',
            'subject_type' => Product::class,
            'subject_id' => $this->product->id,
            'causer_type' => User::class,
            'causer_id' => $this->seller->id,
            'properties' => ['key' => 'value'],
        ]);

        expect($log)->toBeInstanceOf(\App\Models\ActivityLog::class)
            ->and($log->description)->toBe('Test activity')
            ->and($log->properties['key'])->toBe('value');
    });

    test('subject and causer morphTo relationships work', function () {
        $log = \App\Models\ActivityLog::factory()->create([
            'subject_type' => Product::class,
            'subject_id' => $this->product->id,
            'causer_type' => User::class,
            'causer_id' => $this->seller->id,
        ]);

        expect($log->subject->id)->toBe($this->product->id)
            ->and($log->causer->id)->toBe($this->seller->id);
    });
});