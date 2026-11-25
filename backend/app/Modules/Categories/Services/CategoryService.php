<?php

namespace App\Modules\Categories\Services;

use App\Models\Auction;
use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryService
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

    public function find(Category $category): Category
    {
        return $category->load('children');
    }
}
