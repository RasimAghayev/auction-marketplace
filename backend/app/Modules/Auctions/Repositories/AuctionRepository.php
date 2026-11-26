<?php

namespace App\Modules\Auctions\Repositories;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Support\Concerns\ResolvesDatabaseConnection;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;

class AuctionRepository
{
    use ResolvesDatabaseConnection;

    public const ACTIVE_AUCTIONS_CACHE_KEY = 'auctions:active';

    public const ACTIVE_AUCTIONS_CACHE_TTL = 300;

    public function findById(string $id): ?Auction
    {
        if (preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            return Auction::where('_id', new ObjectId($id))->first();
        }

        return Auction::find($id);
    }

    public function active(): array
    {
        if ($cached = Cache::get(self::ACTIVE_AUCTIONS_CACHE_KEY)) {
            return $cached;
        }

        $lock = Cache::lock('rebuild:'.self::ACTIVE_AUCTIONS_CACHE_KEY, 10);

        try {
            $lock->block(5);

            return Cache::remember(self::ACTIVE_AUCTIONS_CACHE_KEY, self::ACTIVE_AUCTIONS_CACHE_TTL, function () {
                return Auction::where('status', AuctionStatus::Active->value)
                    ->with(['product.category', 'seller'])
                    ->latest()
                    ->get()
                    ->all();
            });
        } catch (LockTimeoutException $e) {
            return Cache::get(self::ACTIVE_AUCTIONS_CACHE_KEY) ?? [];
        } finally {
            $lock->release();
        }
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Auction::with(['product.category', 'seller'])
            ->when($filters['status'] ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['seller_id'] ?? null, fn ($q) => $q->where('seller_id', $filters['seller_id']))
            ->when($filters['search'] ?? null, fn ($q) => $q->whereHas('product', fn ($p) => $p->where('title', 'like', "%{$filters['search']}%")))
            ->latest();

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $attributes): Auction
    {
        return Auction::create($attributes);
    }

    public function productExists(string $productId): bool
    {
        return DB::connection($this->resolveConnection())->table('products')->where('_id', $productId)->exists();
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

    public static function invalidateActiveCache(): void
    {
        Cache::forget(self::ACTIVE_AUCTIONS_CACHE_KEY);
    }
}
