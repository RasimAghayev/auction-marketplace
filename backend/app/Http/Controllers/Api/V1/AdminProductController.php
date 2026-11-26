<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ModerateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Modules\Products\Repositories\ProductRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminProductController extends Controller
{
    public function __construct(protected ProductRepository $productRepository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $products = $this->productRepository->adminPaginate([
            'status' => $request->status,
            'search' => $request->search,
            'per_page' => $request->integer('per_page', 20),
        ]);

        return ProductResource::collection($products);
    }

    public function moderate(ModerateProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->validated();

        $product->update([
            'status' => $data['action'] === 'approve' ? ProductStatus::Published->value : ProductStatus::Draft->value,
            'moderation_notes' => $data['reason'] ?? null,
        ]);

        return response()->json([
            'message' => "Product {$data['action']}d successfully.",
            'product' => new ProductResource($product->load(['category', 'seller'])),
        ]);
    }
}
