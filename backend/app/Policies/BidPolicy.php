<?php

namespace App\Policies;

use App\Models\Bid;
use App\Models\User;

class BidPolicy
{
    public function view(User $user, Bid $bid): bool
    {
        return $user->id === $bid->user_id
            || $user->id === $bid->auction->seller_id;
    }
}