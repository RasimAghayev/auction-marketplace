<?php

namespace App\Modules\Bids\Services;

use App\Enums\BidStatus;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;

class BidService
{
    public function place(Auction $auction, User $user, float $amount): Bid
    {
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
