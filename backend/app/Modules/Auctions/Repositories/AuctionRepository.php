<?php

namespace App\Modules\Auctions\Repositories;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AuctionRepository
{
    public const ACTIVE_AUCTIONS_CACHE_KEY = 'auctions:active';
    public const ACTIVE_AUCTIONS_CACHE_TTL = 300;

    public function findById(string $id): ?Auction
    {
        if (preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            return Auction::where('_id', new \MongoDB\BSON\ObjectId($id))->first();
        }

        return Auction::find($id);
    }

    public function active(): array
    {
        return Cache::remember(self::ACTIVE_AUCTIONS_CACHE_KEY, self::ACTIVE_AUCTIONS_CACHE_TTL, function () {
            return Auction::where('status', AuctionStatus::Active->value)
                ->with(['product.category', 'seller'])
                ->latest()
                ->get()
                ->all();
        });
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Auction::with(['product.category', 'seller'])
            ->when($filters['status'] ?? null, fn($q) => $q->where('status', $filters['status']))
            ->when($filters['seller_id'] ?? null, fn($q) => $q->where('seller_id', $filters['seller_id']))
            ->latest();

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $attributes): Auction
    {
        return Auction::create($attributes);
    }

    public function productExists(string $productId): bool
    {
        $connection = config('database.default');
        if (app()->environment('testing') || ($_ENV['APP_ENV'] ?? '') === 'testing' || (getenv('APP_ENV') ?? '') === 'testing') {
            $connection = 'mongodb_testing';
        }

        return DB::connection($connection)->table('products')->where('_id', $productId)->exists();
    }

    public function update(Auction $auction, array $attributes): Auction
    {
        $auction->update($attributes);

        return $auction;
    }

    public function incrementTotalBids(Auction $auction): void
    {
        $auction->increment('total_bids');
    }

    public function invalidateActiveCache(): void
    {
        Cache::forget(self::ACTIVE_AUCTIONS_CACHE_KEY);
    }
}
