<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\AuctionResource;
use App\Http\Resources\Api\V1\BidResource;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Product;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\Notification;
use App\Enums\ProductStatus;
use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
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
            'unread_notifications_count' => Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            'total_spent' => Bid::where('user_id', $user->id)
                ->where('status', BidStatus::Won->value)
                ->sum('amount'),
        ]);
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $products = Product::where('seller_id', $request->user()->id)
            ->with('category', 'auction')
            ->latest()
            ->paginate($request->integer('per_page', 12));

        return ProductResource::collection($products);
    }

    public function auctions(Request $request): AnonymousResourceCollection
    {
        $auctions = Auction::where('seller_id', $request->user()->id)
            ->with('product.category')
            ->latest()
            ->paginate($request->integer('per_page', 12));

        return AuctionResource::collection($auctions);
    }

    public function participated(Request $request): AnonymousResourceCollection
    {
        $auctions = Auction::whereHas('bids', fn($q) => $q->where('user_id', $request->user()->id))
            ->with('product.category', 'seller')
            ->latest()
            ->paginate($request->integer('per_page', 12));

        return AuctionResource::collection($auctions);
    }

    public function won(Request $request): AnonymousResourceCollection
    {
        $bids = Bid::where('user_id', $request->user()->id)
            ->where('status', BidStatus::Won->value)
            ->with('auction.product.category', 'auction.seller')
            ->latest()
            ->paginate($request->integer('per_page', 12));

        return BidResource::collection($bids);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $auctions = Auction::where('seller_id', $request->user()->id)
            ->whereIn('status', [AuctionStatus::Completed->value, AuctionStatus::Cancelled->value, AuctionStatus::Unsold->value])
            ->with('product.category', 'winningBid.user')
            ->latest()
            ->paginate($request->integer('per_page', 12));

        return AuctionResource::collection($auctions);
    }

    public function bids(Request $request): AnonymousResourceCollection
    {
        $bids = Bid::where('user_id', $request->user()->id)
            ->with('auction.product.category', 'auction.seller')
            ->latest()
            ->paginate($request->integer('per_page', 12));

        return BidResource::collection($bids);
    }

    public function notifications(Request $request): AnonymousResourceCollection
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return NotificationResource::collection($notifications);
    }

    public function markNotificationRead(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        $notification->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Notification marked as read.',
            'notification' => new NotificationResource($notification),
        ]);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}