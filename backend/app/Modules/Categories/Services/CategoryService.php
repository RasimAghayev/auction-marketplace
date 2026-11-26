<?php

namespace App\Modules\Categories\Services;

use App\Models\Auction;
use App\Models\Category;
use App\Modules\Categories\Repositories\CategoryRepository;
use Illuminate\Support\Collection;

class CategoryService
{
    public function __construct(
        protected CategoryRepository $repository
    ) {}

    public function list(): Collection
    {
        return $this->repository->list();
    }

    public function find(Category $category): Category
    {
        return $category->load('children');
    }
}
