<?php

namespace App\Modules\Search\Drivers;

use App\Models\Product;
use App\Modules\Search\Contracts\SearchDriver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class SqlSearchDriver implements SearchDriver
{
    public const CACHE_TTL = 60;

    public function search(string $query, array $filters = []): LengthAwarePaginator
    {
        $cacheKey = 'search:' . md5(serialize(['query' => $query, 'filters' => $filters]));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $filters) {
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
        });
    }
}
