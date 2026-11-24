<?php

namespace App\Modules\Auctions\Services;

use App\Enums\AuctionStatus;
use App\Enums\ProductStatus;
use App\Models\Auction;
use DomainException;

class AuctionService
{
    public function create(array $attributes): Auction
    {
        $auction = Auction::create([
            ...$attributes,
            'current_price' => $attributes['starting_price'],
            'status' => AuctionStatus::Scheduled,
        ]);

        $auction->product?->update(['status' => ProductStatus::InAuction]);

        return $auction;
    }

    public function update(Auction $auction, array $attributes): Auction
    {
        $auction->update($attributes);

        return $auction;
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

        return $auction;
    }

    public function cancel(Auction $auction): Auction
    {
        if ($auction->status === AuctionStatus::Active) {
            throw new DomainException('Cannot cancel an active auction.');
        }

        $auction->update(['status' => AuctionStatus::Cancelled]);

        $auction->product?->update(['status' => ProductStatus::Published]);

        return $auction;
    }
}