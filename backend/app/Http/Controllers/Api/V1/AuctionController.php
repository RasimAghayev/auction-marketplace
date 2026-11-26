<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuctionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAuctionRequest;
use App\Http\Requests\Api\V1\UpdateAuctionRequest;
use App\Http\Resources\Api\V1\AuctionResource;
use App\Models\Auction;
use App\Modules\Auctions\Repositories\AuctionRepository;
use App\Modules\Auctions\Services\AuctionService;
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
            'status' => $request->status ?? AuctionStatus::Active->value,
            'per_page' => $request->integer('per_page', 12),
        ]);

        return AuctionResource::collection($auctions);
    }

    public function show(Auction $auction): AuctionResource
    {
        return new AuctionResource($auction->load(['product.category', 'seller', 'bids.user']));
    }

    public function store(StoreAuctionRequest $request): JsonResponse
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

        $data = $request->validated();

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

    public function update(UpdateAuctionRequest $request, Auction $auction): JsonResponse
    {
        $this->authorize('update', $auction);

        $auction = $this->auctions->update($auction, $request->validated());

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
