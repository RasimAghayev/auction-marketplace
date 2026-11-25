<?php

namespace App\Modules\Auctions\Services;

use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use App\Enums\ProductStatus;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Product;
use Carbon\Carbon;
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

    public function complete(Auction $auction): Auction
    {
        if ($auction->status === AuctionStatus::Completed) {
            throw new DomainException('Auction is already completed.');
        }

        $winningBid = Bid::where('auction_id', $auction->id)
            ->where('status', BidStatus::Accepted)
            ->orderByDesc('amount')
            ->first();

        if ($winningBid) {
            $auction->update([
                'status' => AuctionStatus::Completed,
                'winner_id' => $winningBid->user_id,
                'completed_at' => now(),
            ]);

            $winningBid->update(['status' => BidStatus::Winning]);

            $reservePrice = $auction->reserve_price;
            if ($reservePrice && $winningBid->amount < $reservePrice) {
                $auction->product?->update(['status' => ProductStatus::Published]);
            } else {
                $auction->product?->update(['status' => ProductStatus::Sold]);
            }
        } else {
            $auction->update([
                'status' => AuctionStatus::Completed,
                'completed_at' => now(),
            ]);

            $auction->product?->update(['status' => ProductStatus::Published]);
        }

        Cache::forget(self::ACTIVE_AUCTIONS_CACHE_KEY);

        return $auction;
    }

    public function autoExtend(Auction $auction): ?Auction
    {
        if ($auction->status !== AuctionStatus::Active) {
            return null;
        }

        $threshold = config('auction.auto_extend_threshold_seconds', 60);
        $extendDuration = config('auction.auto_extend_duration_seconds', 300);

        $remaining = $auction->end_date->diffInSeconds(now());

        if ($remaining > 0 && $remaining <= $threshold) {
            $auction->update([
                'end_date' => $auction->end_date->addSeconds($extendDuration),
                'version' => ($auction->version ?? 0) + 1,
            ]);

            Cache::forget(self::ACTIVE_AUCTIONS_CACHE_KEY);

            return $auction;
        }

        return null;
    }
}
