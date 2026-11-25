<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Enums\ProductStatus;
use App\Modules\Products\Repositories\ProductRepository;
use App\Modules\Products\Services\ImageUploadService;
use App\Modules\Products\Services\ProductService;
use App\Modules\Search\Services\SearchService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $products,
        protected ProductRepository $productRepository,
        protected SearchService $searchService,
        protected ImageUploadService $imageUploadService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $products = $this->productRepository->paginate([
            'category' => $request->category,
            'search' => $request->search,
            'per_page' => $request->integer('per_page', 12),
        ]);

        return ProductResource::collection($products);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $products = $this->searchService->products($request->q, [
            'category' => $request->category,
            'max_price' => $request->max_price,
            'min_price' => $request->min_price,
            'per_page' => $request->integer('per_page', 12),
        ]);

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'seller', 'auction']));
    }

    public function store(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');

        if (! $this->productRepository->categoryExists($categoryId)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'category_id' => ['The selected category id is invalid.'],
                ],
                'error' => 'validation_error',
            ], 422);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'starting_price' => 'required|numeric|min:0',
            'condition' => 'required|in:new,used,refurbished',
        ]);

        $product = $this->products->create([
            ...$data,
            'seller_id' => $request->user()->id,
            'status' => ProductStatus::Published->value,
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => new ProductResource($product),
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $categoryId = $request->input('category_id');
        if ($categoryId && ! $this->productRepository->categoryExists($categoryId)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'category_id' => ['The selected category id is invalid.'],
                ],
                'error' => 'validation_error',
            ], 422);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'starting_price' => 'sometimes|numeric|min:0',
            'condition' => 'sometimes|in:new,used,refurbished',
            'status' => 'sometimes|in:draft,published,in_auction,sold',
        ]);

        $product = $this->products->update($product, $data);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => new ProductResource($product->load(['category', 'seller'])),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        try {
            $this->products->delete($product);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    public function uploadImages(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $request->validate([
            'images' => 'required|array|max:' . ImageUploadService::MAX_FILES,
            'images.*' => 'image|max:' . ImageUploadService::MAX_FILE_SIZE_KB,
        ]);

        $uploaded = $this->imageUploadService->uploadMultiple(
            $request->file('images', [])
        );

        $product->images = array_merge($product->images ?? [], $uploaded);
        $product->save();

        return response()->json([
            'message' => 'Images uploaded successfully.',
            'images' => $product->images,
        ]);
    }

    public function deleteImage(Request $request, Product $product, string $image): JsonResponse
    {
        $this->authorize('update', $product);

        $images = $product->images ?? [];
        if (($key = array_search($image, $images)) !== false) {
            $this->imageUploadService->delete($image);

            unset($images[$key]);
            $product->images = array_values($images);
            $product->save();
        }

        return response()->json(['message' => 'Image deleted successfully.']);
    }
}
