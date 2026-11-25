<?php

namespace App\Modules\Bids\Services;

use App\Enums\BidStatus;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use DomainException;

class BidService
{
    public function place(Auction $auction, User $user, float $amount): Bid
    {
        if ($auction->status !== \App\Enums\AuctionStatus::Active) {
            throw new DomainException('Auction is not active.');
        }

        if ($auction->seller_id === $user->id) {
            throw new DomainException('You cannot bid on your own auction.');
        }

        if ($auction->end_date && $auction->end_date <= now()) {
            throw new DomainException('Auction has ended.');
        }

        $minimumBid = $auction->current_price + $auction->min_bid_increment;
        if ($amount < $minimumBid) {
            throw new DomainException("Bid must be at least {$minimumBid}.");
        }

        $bid = Bid::create([
            'auction_id' => $auction->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => BidStatus::Pending,
        ]);

        $auction->update([
            'current_price' => $amount,
            'total_bids' => ($auction->total_bids ?? 0) + 1,
        ]);

        $bid->update(['status' => BidStatus::Accepted]);

        return $bid;
    }
}
