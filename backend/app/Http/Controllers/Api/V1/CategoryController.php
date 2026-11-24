<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Auction;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
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

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->load('children'));
    }
}