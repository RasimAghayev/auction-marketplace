<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use App\Modules\Categories\Repositories\CategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AdminCategoryController extends Controller
{
    public function __construct(protected CategoryRepository $categoryRepository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = $this->categoryRepository->adminPaginate([
            'search' => $request->search,
            'per_page' => $request->integer('per_page', 20),
        ]);

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryRepository->create($request->validated());

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => new CategoryResource($category),
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $category = $this->categoryRepository->update($category, $request->validated());

        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => new CategoryResource($category),
        ]);
    }

    public function destroy(Category $category): Response
    {
        if ($category->children()->exists()) {
            return response()->json(['message' => 'Cannot delete category with children.'], 422);
        }

        $this->categoryRepository->delete($category);

        return response()->noContent();
    }
}
