<?php

namespace App\Modules\Products\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use DomainException;

class ProductService
{
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
        if ($product->status === ProductStatus::Published) {
            throw new DomainException('Cannot delete a published product.');
        }

        $product->delete();
    }
}
