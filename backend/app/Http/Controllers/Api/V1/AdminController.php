<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\AuctionResource;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\User;
use App\Models\Product;
use App\Models\Auction;
use App\Models\Category;
use App\Enums\ProductStatus;
use App\Enums\AuctionStatus;
use App\Modules\Users\Services\UserService;
use App\Modules\Products\Repositories\ProductRepository;
use App\Modules\Auctions\Repositories\AuctionRepository;
use App\Modules\Categories\Repositories\CategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected ProductRepository $productRepository,
        protected AuctionRepository $auctionRepository,
        protected CategoryRepository $categoryRepository
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json($this->userService->dashboardStats());
    }

    public function users(Request $request): AnonymousResourceCollection
    {
        $users = $this->userService->list([
            'search' => $request->search,
            'role' => $request->role,
            'per_page' => $request->integer('per_page', 20),
        ]);

        return UserResource::collection($users);
    }

    public function suspendUser(User $user): JsonResponse
    {
        try {
            $user = $this->userService->suspend($user);
        } catch (\DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 403);
        }

        return response()->json([
            'message' => 'User suspended successfully.',
            'user' => new UserResource($user->fresh()->load('roles')),
        ]);
    }

    public function unsuspendUser(User $user): JsonResponse
    {
        $user = $this->userService->unsuspend($user);

        return response()->json([
            'message' => 'User unsuspended successfully.',
            'user' => new UserResource($user->fresh()->load('roles')),
        ]);
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $products = $this->productRepository->adminPaginate([
            'status' => $request->status,
            'search' => $request->search,
            'per_page' => $request->integer('per_page', 20),
        ]);

        return ProductResource::collection($products);
    }

    public function moderateProduct(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'nullable|string|max:500',
        ]);

        $product->update([
            'status' => $request->action === 'approve' ? ProductStatus::Published->value : ProductStatus::Draft->value,
            'moderation_notes' => $request->reason,
        ]);

        return response()->json([
            'message' => "Product {$request->action}d successfully.",
            'product' => new ProductResource($product->load(['category', 'seller'])),
        ]);
    }

    public function auctions(Request $request): AnonymousResourceCollection
    {
        $auctions = $this->auctionRepository->paginate([
            'status' => $request->status,
            'search' => $request->search,
            'per_page' => $request->integer('per_page', 20),
        ]);

        return AuctionResource::collection($auctions);
    }

    public function manageAuction(Request $request, Auction $auction): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:cancel,extend',
            'end_date' => 'sometimes|date|after:now',
        ]);

        if ($request->action === 'cancel') {
            $auction->update(['status' => AuctionStatus::Cancelled->value]);
            $message = 'Auction cancelled successfully.';
        } elseif ($request->action === 'extend') {
            $auction->update(['end_date' => $request->end_date]);
            $message = 'Auction extended successfully.';
        }

        return response()->json([
            'message' => $message,
            'auction' => new AuctionResource($auction->load(['product.category', 'seller'])),
        ]);
    }

    public function categories(Request $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->adminPaginate([
            'search' => $request->search,
            'per_page' => $request->integer('per_page', 20),
        ]);

        return CategoryResource::collection($categories);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        // Manual validation for slug uniqueness and parent_id
        $slug = $request->input('slug');
        $slugExists = $this->categoryRepository->findBySlug($slug);

        if ($slugExists) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'slug' => ['The slug has already been taken.'],
                ],
                'error' => 'validation_error',
            ], 422);
        }

        $parentId = $request->input('parent_id');
        if ($parentId) {
            $parentExists = $this->categoryRepository->findById($parentId);

            if (!$parentExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'parent_id' => ['The selected parent id is invalid.'],
                    ],
                    'error' => 'validation_error',
                ], 422);
            }
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);

        $category = $this->categoryRepository->create($data);

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => new CategoryResource($category),
        ], 201);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        // Manual validation for slug uniqueness and parent_id
        $slug = $request->input('slug');
        if ($slug && $slug !== $category->slug) {
            $slugExists = $this->categoryRepository->findBySlug($slug);

            if ($slugExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'slug' => ['The slug has already been taken.'],
                    ],
                    'error' => 'validation_error',
                ], 422);
            }
        }

        $parentId = $request->input('parent_id');
        if ($parentId) {
            $parentExists = $this->categoryRepository->findById($parentId);

            if (!$parentExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'parent_id' => ['The selected parent id is invalid.'],
                    ],
                    'error' => 'validation_error',
                ], 422);
            }
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $category = $this->categoryRepository->update($category, $data);

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => new CategoryResource($category),
        ]);
    }

    public function deleteCategory(Category $category): JsonResponse
    {
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with children.',
            ], 422);
        }

        $this->categoryRepository->delete($category);

        return response()->json(['message' => 'Category deleted successfully.']);
    }

    public function reports(): JsonResponse
    {
        return response()->json([
            'daily_registrations' => User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->get(),
            'daily_auctions' => Auction::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->get(),
            'top_categories' => Category::withCount('products')
                ->orderByDesc('products_count')
                ->take(10)
                ->get(),
            'top_sellers' => User::withCount(['products as products_count' => fn($q) => $q->where('status', ProductStatus::Published->value)])
                ->orderByDesc('products_count')
                ->take(10)
                ->get(),
        ]);
    }
}
