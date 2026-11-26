<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ManageAuctionRequest;
use App\Http\Resources\Api\V1\AuctionResource;
use App\Models\Auction;
use App\Modules\Auctions\Repositories\AuctionRepository;
use App\Modules\Auctions\Services\AuctionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminAuctionController extends Controller
{
    public function __construct(
        protected AuctionService $auctionService,
        protected AuctionRepository $auctionRepository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $auctions = $this->auctionRepository->paginate([
            'status' => $request->status,
            'search' => $request->search,
            'per_page' => $request->integer('per_page', 20),
        ]);

        return AuctionResource::collection($auctions);
    }

    public function manage(ManageAuctionRequest $request, Auction $auction): JsonResponse
    {
        $data = $request->validated();

        if ($data['action'] === 'cancel') {
            try {
                $auction = $this->auctionService->cancel($auction);
            } catch (\DomainException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            $message = 'Auction cancelled successfully.';
        } elseif ($data['action'] === 'extend') {
            try {
                $auction->update(['end_date' => $data['end_date'], 'version' => ($auction->version ?? 0) + 1]);
                AuctionRepository::invalidateActiveCache();
            } catch (\DomainException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            $message = 'Auction extended successfully.';
        }

        return response()->json([
            'message' => $message,
            'auction' => new AuctionResource($auction->load(['product.category', 'seller'])),
        ]);
    }
}
