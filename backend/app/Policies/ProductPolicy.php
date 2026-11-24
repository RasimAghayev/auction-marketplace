<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->canCreateProducts();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id;
    }

    public function createAuction(User $user, Product $product): bool
    {
        return $user->id === $product->seller_id
            && $product->canBeAuctioned();
    }
}