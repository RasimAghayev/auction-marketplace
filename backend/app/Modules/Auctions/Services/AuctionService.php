<?php

namespace App\Modules\Auctions\Services;

use App\Enums\AuctionStatus;
use App\Enums\ProductStatus;
use App\Models\Auction;
use App\Models\Product;
use DomainException;
use Illuminate\Support\Facades\Cache;

class AuctionService
{
    public const ACTIVE_AUCTIONS_CACHE_KEY = 'auctions:active';
    public const ACTIVE_AUCTIONS_CACHE_TTL = 300;

    public function create(array $attributes): Auction
    {
        $auction = Auction::create([
            ...$attributes,
            'current_price' => $attributes['starting_price'],
            'status' => AuctionStatus::Scheduled,
        ]);

        $auction->product?->update(['status' => ProductStatus::InAuction]);

        Cache::forget(self::ACTIVE_AUCTIONS_CACHE_KEY);

        return $auction;
    }

    public function update(Auction $auction, array $attributes): Auction
    {
        $auction->update($attributes);

        Cache::forget(self::ACTIVE_AUCTIONS_CACHE_KEY);

        return $auction;
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

    public function start(Auction $auction): Auction
    {
        if ($auction->status !== AuctionStatus::Scheduled) {
            throw new DomainException('Auction can only be started from scheduled status.');
        }

        $auction->update([
            'status' => AuctionStatus::Active,
            'started_at' => now(),
        ]);

        Cache::forget(self::ACTIVE_AUCTIONS_CACHE_KEY);

        return $auction;
    }

    public function cancel(Auction $auction): Auction
    {
        if ($auction->status === AuctionStatus::Active) {
            throw new DomainException('Cannot cancel an active auction.');
        }

        $auction->update(['status' => AuctionStatus::Cancelled]);

        $auction->product?->update(['status' => ProductStatus::Published]);

        Cache::forget(self::ACTIVE_AUCTIONS_CACHE_KEY);

        return $auction;
    }
}
