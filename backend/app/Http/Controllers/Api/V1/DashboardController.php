<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\AuctionResource;
use App\Http\Resources\Api\V1\BidResource;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Notification;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Users\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected NotificationService $notificationService
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json($this->dashboardService->overview($user));
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $products = $this->dashboardService->products($request->user(), $request->integer('per_page', 12));

        return ProductResource::collection($products);
    }

    public function auctions(Request $request): AnonymousResourceCollection
    {
        $auctions = $this->dashboardService->auctions($request->user(), $request->integer('per_page', 12));

        return AuctionResource::collection($auctions);
    }

    public function participated(Request $request): AnonymousResourceCollection
    {
        $auctions = $this->dashboardService->participated($request->user(), $request->integer('per_page', 12));

        return AuctionResource::collection($auctions);
    }

    public function won(Request $request): AnonymousResourceCollection
    {
        $bids = $this->dashboardService->won($request->user(), $request->integer('per_page', 12));

        return BidResource::collection($bids);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $auctions = $this->dashboardService->history($request->user(), $request->integer('per_page', 12));

        return AuctionResource::collection($auctions);
    }

    public function bids(Request $request): AnonymousResourceCollection
    {
        $bids = $this->dashboardService->bids($request->user(), $request->integer('per_page', 12));

        return BidResource::collection($bids);
    }

    public function notifications(Request $request): AnonymousResourceCollection
    {
        $notifications = $this->notificationService->forUser($request->user()->id, [
            'per_page' => $request->integer('per_page', 20),
        ]);

        return NotificationResource::collection($notifications);
    }

    public function markNotificationRead(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('view', $notification);

        $notification = $this->notificationService->markAsRead($notification);

        return response()->json([
            'message' => 'Notification marked as read.',
            'notification' => new NotificationResource($notification),
        ]);
    }

    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
