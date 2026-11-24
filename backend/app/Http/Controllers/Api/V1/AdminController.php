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
use App\Enums\UserRole;
use App\Enums\ProductStatus;
use App\Enums\AuctionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        return response()->json([
            'users_count' => User::count(),
            'products_count' => Product::count(),
            'auctions_count' => Auction::count(),
            'categories_count' => Category::count(),
            'pending_products_count' => Product::where('status', ProductStatus::Draft->value)->count(),
            'active_auctions_count' => Auction::where('status', AuctionStatus::Active->value)->count(),
            'revenue' => Auction::where('status', AuctionStatus::Completed->value)
                ->whereNotNull('winning_bid_id')
                ->with('winningBid')
                ->get()
                ->sum(fn($a) => $a->winningBid?->amount ?? 0),
        ]);
    }

    public function users(Request $request): AnonymousResourceCollection
    {
        $users = User::with('roles')
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%"))
            ->when($request->role, fn($q) => $q->whereHas('roles', fn($r) => $r->where('name', $request->role)))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return UserResource::collection($users);
    }

    public function suspendUser(User $user): JsonResponse
    {
        if ($user->hasRole(UserRole::Admin->value)) {
            return response()->json([
                'message' => 'Cannot suspend an admin user.',
            ], 403);
        }

        $user->update(['is_active' => false]);

        return response()->json([
            'message' => 'User suspended successfully.',
            'user' => new UserResource($user->fresh()->load('roles')),
        ]);
    }

    public function unsuspendUser(User $user): JsonResponse
    {
        $user->update(['is_active' => true]);

        return response()->json([
            'message' => 'User unsuspended successfully.',
            'user' => new UserResource($user->fresh()->load('roles')),
        ]);
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $products = Product::with(['category', 'seller'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate($request->integer('per_page', 20));

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
        $auctions = Auction::with(['product.category', 'seller'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->integer('per_page', 20));

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
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return CategoryResource::collection($categories);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $connection = config('database.default');
        if (app()->environment('testing')) {
            $connection = 'mongodb_testing';
        }

        // Manual validation for slug uniqueness and parent_id
        $slug = $request->input('slug');
        $slugExists = DB::connection($connection)->table('categories')->where('slug', $slug)->exists();
        
        if ($slugExists) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => [
                    'slug' => ['The slug has already been taken.']
                ],
                'error' => 'validation_error'
            ], 422);
        }

        $parentId = $request->input('parent_id');
        if ($parentId) {
            $parentExists = DB::connection($connection)->table('categories')->where('id', $parentId)->exists();
            
            if (!$parentExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'parent_id' => ['The selected parent id is invalid.']
                    ],
                    'error' => 'validation_error'
                ], 422);
            }
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => new CategoryResource($category),
        ], 201);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $connection = config('database.default');
        if (app()->environment('testing')) {
            $connection = 'mongodb_testing';
        }

        // Manual validation for slug uniqueness and parent_id
        $slug = $request->input('slug');
        if ($slug && $slug !== $category->slug) {
            $slugExists = DB::connection($connection)->table('categories')->where('slug', $slug)->exists();
            
            if ($slugExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'slug' => ['The slug has already been taken.']
                    ],
                    'error' => 'validation_error'
                ], 422);
            }
        }

        $parentId = $request->input('parent_id');
        if ($parentId) {
            $parentExists = DB::connection($connection)->table('categories')->where('id', $parentId)->exists();
            
            if (!$parentExists) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'parent_id' => ['The selected parent id is invalid.']
                    ],
                    'error' => 'validation_error'
                ], 422);
            }
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => new CategoryResource($category),
        ]);
    }

    public function deleteCategory(Category $category): Response
    {
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with children.',
            ], 422);
        }

        $category->delete();

        return response()->noContent();
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