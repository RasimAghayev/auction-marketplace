<?php

namespace App\Modules\Search\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchRepository
{
    public function products(string $query, array $filters = []): LengthAwarePaginator
    {
        $qb = Product::with(['category', 'seller'])
            ->where('status', 'published')
            ->where('title', 'like', "%{$query}%");

        if (isset($filters['category'])) {
            $qb->whereHas('category', fn($c) => $c->where('slug', $filters['category']));
        }

        if (isset($filters['max_price'])) {
            $qb->where('starting_price', '<=', $filters['max_price']);
        }

        if (isset($filters['min_price'])) {
            $qb->where('starting_price', '>=', $filters['min_price']);
        }

        return $qb->latest()->paginate($filters['per_page'] ?? 12);
    }
}
