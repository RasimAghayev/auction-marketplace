<?php

namespace App\Modules\Categories\Repositories;

use App\Models\Auction;
use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryRepository
{
    public function list(): Collection
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        foreach ($categories as $category) {
            $category->products_count = $category->products()->count();
            $category->active_auctions_count = Auction::whereHas('product', function ($q) use ($category) {
                $q->where('category_id', (string) $category->id);
            })->where('status', 'active')->count();
        }

        return $categories;
    }

    public function findById(string $id): ?Category
    {
        if (preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            return Category::where('_id', new \MongoDB\BSON\ObjectId($id))->first();
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
}
