<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Enums\ProductStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::with(['category', 'seller'])
            ->where('status', ProductStatus::Published->value);

        if ($request->has('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->has('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $products = $query->latest()->paginate($request->integer('per_page', 12));

        return ProductResource::collection($products);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $query = Product::with(['category', 'seller'])
            ->where('status', ProductStatus::Published->value);

        if ($request->has('q')) {
            $query->where('title', 'like', "%{$request->q}%");
        }

        if ($request->has('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        $products = $query->latest()->paginate($request->integer('per_page', 12));

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'seller', 'auction']));
    }

    public function store(Request $request): JsonResponse
    {
        $connection = config('database.default');
        if (app()->environment('testing') || ($_ENV['APP_ENV'] ?? '') === 'testing' || (getenv('APP_ENV') ?? '') === 'testing') {
            $connection = 'mongodb_testing';
        }

        // Manual validation for category_id to use correct connection
        $categoryId = $request->input('category_id');
        $categoryExists = DB::connection($connection)->table('categories')->where('_id', $categoryId)->exists();
        
        if (!$categoryExists) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'category_id' => ['The selected category id is invalid.']
                ],
                'error' => 'validation_error'
            ], 422);
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'starting_price' => 'required|numeric|min:0',
            'condition' => 'required|in:new,used,refurbished',
        ]);

        $product = Product::create([
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

        $connection = config('database.default');
        if (app()->environment('testing') || ($_ENV['APP_ENV'] ?? '') === 'testing' || (getenv('APP_ENV') ?? '') === 'testing') {
            $connection = 'mongodb_testing';
        }

        // Manual validation for category_id to use correct connection
        $categoryId = $request->input('category_id');
        if ($categoryId) {
            $categoryExists = DB::connection($connection)->table('categories')->where('_id', $categoryId)->exists();
            
            if (!$categoryExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'category_id' => ['The selected category id is invalid.']
                    ],
                    'error' => 'validation_error'
                ], 422);
            }
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'starting_price' => 'sometimes|numeric|min:0',
            'condition' => 'sometimes|in:new,used,refurbished',
            'status' => 'sometimes|in:draft,published,in_auction,sold',
        ]);

        $product->update($data);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => new ProductResource($product->load(['category', 'seller'])),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->authorize('delete', $product);
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully.']);
    }

    public function uploadImages(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $request->validate([
            'images.*' => 'required|image|max:5120',
        ]);

        $uploaded = [];
        foreach ($request->file('images', []) as $image) {
            $path = $image->store('products', 'public');
            $uploaded[] = $path;
        }

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
            unset($images[$key]);
            $product->images = array_values($images);
            $product->save();
        }

        return response()->json(['message' => 'Image deleted successfully.']);
    }
}