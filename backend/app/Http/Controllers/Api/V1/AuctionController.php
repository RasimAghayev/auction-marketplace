<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuctionResource;
use App\Models\Auction;
use App\Models\Product;
use App\Enums\AuctionStatus;
use App\Enums\ProductStatus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AuctionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Auction::with(['product.category', 'seller'])
            ->where('status', AuctionStatus::Active->value);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $auctions = $query->latest()->paginate($request->integer('per_page', 12));

        return AuctionResource::collection($auctions);
    }

    public function show(Auction $auction): AuctionResource
    {
        return new AuctionResource($auction->load(['product.category', 'seller', 'bids.user']));
    }

    public function store(Request $request): JsonResponse
    {
        $connection = config('database.default');
        if (app()->environment('testing') || ($_ENV['APP_ENV'] ?? '') === 'testing' || (getenv('APP_ENV') ?? '') === 'testing') {
            $connection = 'mongodb_testing';
        }

        // Manual validation for product_id to use correct connection
        $productId = $request->input('product_id');
        $productExists = DB::connection($connection)->table('products')->where('_id', $productId)->exists();
        
        if (!$productExists) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'product_id' => ['The selected product id is invalid.']
                ],
                'error' => 'validation_error'
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

        $product = Product::findOrFail($data['product_id']);
        $this->authorize('createAuction', $product);

        $auction = Auction::create([
            ...$data,
            'product_id' => $product->id,
            'seller_id' => $request->user()->id,
            'current_price' => $data['starting_price'],
            'status' => AuctionStatus::Scheduled->value,
        ]);

        $product->update(['status' => ProductStatus::InAuction->value]);

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

        $auction->update($data);

        return response()->json([
            'message' => 'Auction updated successfully.',
            'auction' => new AuctionResource($auction->load(['product', 'seller'])),
        ]);
    }

    public function start(Auction $auction): JsonResponse
    {
        $this->authorize('start', $auction);

        if ($auction->status !== AuctionStatus::Scheduled->value) {
            return response()->json([
                'message' => 'Auction can only be started from scheduled status.',
            ], 422);
        }

        $auction->update([
            'status' => AuctionStatus::Active->value,
            'started_at' => now(),
        ]);

        return response()->json([
            'message' => 'Auction started successfully.',
            'auction' => new AuctionResource($auction),
        ]);
    }

    public function cancel(Auction $auction): JsonResponse
    {
        $this->authorize('cancel', $auction);

        if ($auction->status === AuctionStatus::Active) {
            return response()->json([
                'message' => 'Cannot cancel an active auction.',
            ], 422);
        }

        $auction->update(['status' => AuctionStatus::Cancelled->value]);

        $auction->product->update(['status' => ProductStatus::Published->value]);

        return response()->json([
            'message' => 'Auction cancelled successfully.',
            'auction' => new AuctionResource($auction),
        ]);
    }
}