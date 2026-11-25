<?php

namespace App\Modules\Users\Services;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Product;
use App\Models\User;
use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DashboardService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function overview(User $user): array
    {
        return [
            'products_count' => Product::where('seller_id', $user->id)->count(),
            'active_auctions_count' => Auction::where('seller_id', $user->id)
                ->where('status', AuctionStatus::Active->value)
                ->count(),
            'won_auctions_count' => Bid::where('user_id', $user->id)
                ->where('status', BidStatus::Won->value)
                ->count(),
            'pending_bids_count' => Bid::where('user_id', $user->id)
                ->where('status', BidStatus::Pending->value)
                ->count(),
            'unread_notifications_count' => $this->notificationService->unreadCount($user->id),
            'total_spent' => Bid::where('user_id', $user->id)
                ->where('status', BidStatus::Won->value)
                ->sum('amount'),
        ];
    }

    public function products(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return Product::where('seller_id', $user->id)
            ->with('category', 'auction')
            ->latest()
            ->paginate($perPage);
    }

    public function auctions(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return Auction::where('seller_id', $user->id)
            ->with('product.category')
            ->latest()
            ->paginate($perPage);
    }

    public function participated(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return Auction::whereHas('bids', fn($q) => $q->where('user_id', $user->id))
            ->with('product.category', 'seller')
            ->latest()
            ->paginate($perPage);
    }

    public function won(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return Bid::where('user_id', $user->id)
            ->where('status', BidStatus::Won->value)
            ->with('auction.product.category', 'auction.seller')
            ->latest()
            ->paginate($perPage);
    }

    public function history(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return Auction::where('seller_id', $user->id)
            ->whereIn('status', [AuctionStatus::Completed->value, AuctionStatus::Cancelled->value, AuctionStatus::Ended->value])
            ->with('product.category', 'winningBid.user')
            ->latest()
            ->paginate($perPage);
    }

    public function bids(User $user, int $perPage = 12): LengthAwarePaginator
    {
        return Bid::where('user_id', $user->id)
            ->with('auction.product.category', 'auction.seller')
            ->latest()
            ->paginate($perPage);
    }
}
