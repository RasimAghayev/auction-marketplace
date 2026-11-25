<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BidResource;
use App\Models\Auction;
use App\Models\Bid;
use App\Enums\AuctionStatus;
use App\Enums\BidStatus;
use App\Modules\Bids\Services\BidService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BidController extends Controller
{
    public function __construct(
        protected BidService $bids
    ) {}

    public function index(Auction $auction): AnonymousResourceCollection
    {
        $bids = $auction->bids()
            ->with('user')
            ->latest('amount')
            ->paginate(20);

        return BidResource::collection($bids);
    }

    public function store(Request $request, Auction $auction): JsonResponse
    {
        if ($auction->status !== AuctionStatus::Active) {
            return response()->json([
                'message' => 'Auction is not active.',
            ], 422);
        }

        if ($auction->seller_id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot bid on your own auction.',
            ], 403);
        }

        if ($auction->end_date <= now()) {
            return response()->json([
                'message' => 'Auction has ended.',
            ], 422);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $minimumBid = $auction->current_price + $auction->min_bid_increment;
        if ($data['amount'] < $minimumBid) {
            return response()->json([
                'message' => "Bid must be at least {$minimumBid}.",
            ], 422);
        }

        $bid = $this->bids->place($auction, $request->user(), $data['amount']);

        return response()->json([
            'message' => 'Bid placed successfully.',
            'bid' => new BidResource($bid->load('user')),
        ], 201);
    }

    public function myBids(Request $request): AnonymousResourceCollection
    {
        $bids = Bid::with(['auction.product.category'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return BidResource::collection($bids);
    }
}
