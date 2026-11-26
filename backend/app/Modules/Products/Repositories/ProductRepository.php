<?php

namespace App\Modules\Products\Repositories;

use App\Models\Product;
use App\Support\Concerns\ResolvesDatabaseConnection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;

class ProductRepository
{
    use ResolvesDatabaseConnection;

    public function findById(string $id): ?Product
    {
        if (preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            return Product::where('_id', new ObjectId($id))->first();
        }

        return Product::find($id);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::where('slug', $slug)->first();
    }

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Product::with(['category', 'seller'])
            ->when($filters['status'] ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['category'] ?? null, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $filters['category'])))
            ->when($filters['search'] ?? null, fn ($q) => $q->where('title', 'like', "%{$filters['search']}%"))
            ->when($filters['seller_id'] ?? null, fn ($q) => $q->where('seller_id', $filters['seller_id']))
            ->published();

        return $query->latest()->paginate($filters['per_page'] ?? 12);
    }

    public function adminPaginate(array $filters = []): LengthAwarePaginator
    {
        $query = Product::with(['category', 'seller'])
            ->when($filters['status'] ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['category'] ?? null, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $filters['category'])))
            ->when($filters['search'] ?? null, fn ($q) => $q->where('title', 'like', "%{$filters['search']}%"))
            ->when($filters['seller_id'] ?? null, fn ($q) => $q->where('seller_id', $filters['seller_id']));

        return $query->latest()->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $attributes): Product
    {
        return Product::create($attributes);
    }

    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function countByStatus(string $status): int
    {
        return Product::where('status', $status)->count();
    }

    public function categoryExists(string $categoryId): bool
    {
        return DB::connection($this->resolveConnection())->table('categories')->where('_id', $categoryId)->exists();
    }
}
