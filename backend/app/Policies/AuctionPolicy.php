<?php

namespace App\Policies;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\Product;
use App\Models\User;

class AuctionPolicy
{
    public function createAuction(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function update(User $user, Auction $auction): bool
    {
        return $user->id === $auction->seller_id
            && $auction->status === AuctionStatus::Scheduled;
    }

    public function start(User $user, Auction $auction): bool
    {
        return $user->id === $auction->seller_id;
    }

    public function cancel(User $user, Auction $auction): bool
    {
        return $user->id === $auction->seller_id;
    }

    public function complete(User $user, Auction $auction): bool
    {
        return $user->id === $auction->seller_id;
    }
}
