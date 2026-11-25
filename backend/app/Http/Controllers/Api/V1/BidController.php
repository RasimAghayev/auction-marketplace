<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BidResource;
use App\Models\Auction;
use App\Models\Bid;
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
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $bid = $this->bids->place($auction, $request->user(), $data['amount']);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

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
