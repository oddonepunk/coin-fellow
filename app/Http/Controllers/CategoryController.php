<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\CreateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Services\Categories\DTO\CategoryDTO;
use App\Services\Categories\Interfaces\CategoryServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryServiceInterface $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getAllCategories($request->user());

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
            'message' => 'Categories retrieved successfully'
        ]);
    }

    public function defaults(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getDefaultCategories();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
            'message' => 'Default categories retrieved successfully'
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getUserCategories($request->user());

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
            'message' => 'User categories retrieved successfully'
        ]);
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $dto = CategoryDTO::from($request->validated());
        $category = $this->categoryService->createCategory($request->user(), $dto);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
            'message' => 'Category created successfully'
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
            'message' => 'Category retrieved successfully'
        ]);
    }

    public function update(CreateCategoryRequest $request, int $id): JsonResponse
    {
        $dto = CategoryDTO::from($request->validated());
        $category = $this->categoryService->updateCategory($request->user(), $id, $dto);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
            'message' => 'Category updated successfully'
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->categoryService->deleteCategory($request->user(), $id);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}