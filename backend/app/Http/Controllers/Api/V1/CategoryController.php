<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use App\Modules\Categories\Services\CategoryService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categories
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->categories->list());
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($this->categories->find($category));
    }
}
