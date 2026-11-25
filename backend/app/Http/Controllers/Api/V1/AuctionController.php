<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuctionResource;
use App\Models\Auction;
use App\Enums\AuctionStatus;
use App\Modules\Auctions\Repositories\AuctionRepository;
use App\Modules\Auctions\Services\AuctionService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuctionController extends Controller
{
    public function __construct(
        protected AuctionService $auctions,
        protected AuctionRepository $auctionRepository
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $auctions = $this->auctionRepository->paginate([
            'status' => $request->status,
            'per_page' => $request->integer('per_page', 12),
        ]);

        return AuctionResource::collection($auctions);
    }

    public function show(Auction $auction): AuctionResource
    {
        return new AuctionResource($auction->load(['product.category', 'seller', 'bids.user']));
    }

    public function store(Request $request): JsonResponse
    {
        $productId = $request->input('product_id');

        if (! $this->auctionRepository->productExists($productId)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'product_id' => ['The selected product id is invalid.'],
                ],
                'error' => 'validation_error',
            ], 422);
        }

        $data = $request->validate([
            'product_id' => 'required',
            'starting_price' => 'required|numeric|min:0.01',
            'min_bid_increment' => 'required|numeric|min:0.01',
            'reserve_price' => 'nullable|numeric|min:0',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
        ]);

        $auction = $this->auctions->create([
            ...$data,
            'product_id' => $productId,
            'seller_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Auction created successfully.',
            'auction' => new AuctionResource($auction->load(['product', 'seller'])),
        ], 201);
    }

    public function update(Request $request, Auction $auction): JsonResponse
    {
        $this->authorize('update', $auction);

        $data = $request->validate([
            'starting_price' => 'sometimes|numeric|min:0.01',
            'min_bid_increment' => 'sometimes|numeric|min:0.01',
            'reserve_price' => 'nullable|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
        ]);

        $auction = $this->auctions->update($auction, $data);

        return response()->json([
            'message' => 'Auction updated successfully.',
            'auction' => new AuctionResource($auction->load(['product', 'seller'])),
        ]);
    }

    public function start(Auction $auction): JsonResponse
    {
        $this->authorize('start', $auction);

        try {
            $auction = $this->auctions->start($auction);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Auction started successfully.',
            'auction' => new AuctionResource($auction),
        ]);
    }

    public function cancel(Auction $auction): JsonResponse
    {
        $this->authorize('cancel', $auction);

        try {
            $auction = $this->auctions->cancel($auction);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Auction cancelled successfully.',
            'auction' => new AuctionResource($auction),
        ]);
    }

    public function complete(Auction $auction): JsonResponse
    {
        $this->authorize('complete', $auction);

        try {
            $auction = $this->auctions->complete($auction);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Auction completed successfully.',
            'auction' => new AuctionResource($auction->load(['winningBid.user'])),
        ]);
    }
}
