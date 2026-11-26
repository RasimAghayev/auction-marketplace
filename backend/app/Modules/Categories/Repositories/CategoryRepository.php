<?php

namespace App\Modules\Categories\Repositories;

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use MongoDB\BSON\ObjectId;

class CategoryRepository
{
    public function list(): Collection
    {
        $categories = Category::where('is_active', true)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $activeAuctionCounts = Auction::where('status', AuctionStatus::Active->value)
            ->with('product')
            ->get()
            ->groupBy(fn (Auction $auction) => (string) ($auction->product->category_id ?? ''))
            ->map(fn ($group) => $group->count());

        foreach ($categories as $category) {
            $category->active_auctions_count = $activeAuctionCounts[(string) $category->id] ?? 0;
        }

        return $categories;
    }

    public function findById(string $id): ?Category
    {
        if (preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            return Category::where('_id', new ObjectId($id))->first();
        }

        return Category::find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)->first();
    }

    public function roots(): Collection
    {
        return Category::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function children(string $parentId): Collection
    {
        return Category::where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function create(array $attributes): Category
    {
        return Category::create($attributes);
    }

    public function update(Category $category, array $attributes): Category
    {
        $category->update($attributes);

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function adminPaginate(array $filters = []): LengthAwarePaginator
    {
        $query = Category::with('children')
            ->when($filters['search'] ?? null, fn ($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->when($filters['is_active'] ?? null, fn ($q) => $q->where('is_active', $filters['is_active']))
            ->whereNull('parent_id')
            ->latest();

        return $query->paginate($filters['per_page'] ?? 20);
    }
}
