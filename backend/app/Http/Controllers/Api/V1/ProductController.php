<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Requests\Api\V1\UploadProductImagesRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Modules\Products\Repositories\ProductRepository;
use App\Modules\Products\Services\ImageUploadService;
use App\Modules\Products\Services\ProductService;
use App\Modules\Search\Services\SearchService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $products,
        protected ProductRepository $productRepository,
        protected SearchService $searchService,
        protected ImageUploadService $imageUploadService
    ) {}

    #[OA\Get(path: '/products', tags: ['Products'], summary: 'List published products', responses: [
        new OA\Response(response: 200, description: 'Paginated product list'),
    ])]
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
        $products = $this->searchService->products($request->input('q', ''), [
            'category' => $request->category,
            'max_price' => $request->max_price,
            'min_price' => $request->min_price,
            'per_page' => $request->integer('per_page', 12),
        ]);

        return ProductResource::collection($products);
    }

    #[OA\Get(path: '/products/{product}', tags: ['Products'], summary: 'Show a product', responses: [
        new OA\Response(response: 200, description: 'Product detail'),
        new OA\Response(response: 404, description: 'Product not found'),
    ])]
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'seller', 'auction']));
    }

    #[OA\Post(path: '/products', tags: ['Products'], summary: 'Create a product', security: [['bearerAuth' => []]], responses: [
        new OA\Response(response: 201, description: 'Product created'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation error'),
    ])]
    public function store(StoreProductRequest $request): JsonResponse
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

        $product = $this->products->create([
            ...$request->validated(),
            'seller_id' => $request->user()->id,
            'status' => ProductStatus::Published->value,
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => new ProductResource($product),
        ], 201);
    }

    #[OA\Put(path: '/products/{product}', tags: ['Products'], summary: 'Update a product', security: [['bearerAuth' => []]], responses: [
        new OA\Response(response: 200, description: 'Product updated'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation error'),
    ])]
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
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

        $product = $this->products->update($product, $request->validated());

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => new ProductResource($product->load(['category', 'seller'])),
        ]);
    }

    #[OA\Delete(path: '/products/{product}', tags: ['Products'], summary: 'Delete a product', security: [['bearerAuth' => []]], responses: [
        new OA\Response(response: 204, description: 'Product deleted'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Cannot delete product with active auction'),
    ])]
    public function destroy(Product $product): Response
    {
        $this->authorize('delete', $product);

        try {
            $this->products->delete($product);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->noContent();
    }

    public function uploadImages(UploadProductImagesRequest $request, Product $product): JsonResponse
    {
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
