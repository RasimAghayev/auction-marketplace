<?php

namespace App\Modules\Bids\Repositories;

use App\Models\Bid;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use MongoDB\BSON\ObjectId;

class BidRepository
{
    public function findById(string $id): ?Bid
    {
        if (preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            return Bid::where('_id', new ObjectId($id))->first();
        }

        return Bid::find($id);
    }

    public function forAuction(string $auctionId, array $filters = []): LengthAwarePaginator
    {
        $query = Bid::with('user')
            ->where('auction_id', $auctionId)
            ->latest();

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function byUser(string $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Bid::with('auction.product.category', 'auction.seller')
            ->where('user_id', $userId)
            ->latest();

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function winning(): LengthAwarePaginator
    {
        return Bid::with('auction.product.category', 'auction.seller')
            ->winning()
            ->latest()
            ->paginate(20);
    }

    public function update(Bid $bid, array $attributes): Bid
    {
        $bid->update($attributes);

        return $bid;
    }
}
