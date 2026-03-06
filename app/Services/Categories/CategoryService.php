<?php

namespace App\Services\Categories;

use App\Models\Category;
use App\Models\User;
use App\Services\Categories\DTO\CategoryDTO;
use App\Services\Categories\Interfaces\CategoryServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CategoryService implements CategoryServiceInterface
{
    public function getAllCategories(User $user): Collection
    {
        return Category::where(function ($query) use ($user) {
            $query->where('is_default', true)
                  ->orWhere('user_id', $user->id);
        })
        ->orderBy('is_default', 'desc')
        ->orderBy('name')
        ->get();
    }

    public function getDefaultCategories(): Collection
    {
        return Category::where('is_default', true)
            ->orderBy('name')
            ->get();
    }

    public function getUserCategories(User $user): Collection
    {
        return Category::where('user_id', $user->id)
            ->orderBy('name')
            ->get();
    }

    public function createCategory(User $user, CategoryDTO $dto): Category
    {
        return Category::create([
            'name' => $dto->name,
            'icon' => $dto->icon,
            'color' => $dto->color,
            'user_id' => $user->id,
            'is_default' => false,
        ]);
    }

    public function updateCategory(User $user, int $categoryId, CategoryDTO $dto): Category
    {
        $category = $this->getCategoryById($categoryId);

        if ($category->is_default) {
            throw ValidationException::withMessages([
                'category' => ['Cannot modify default category'],
            ]);
        }

        if ($category->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'category' => ['You do not own this category'],
            ]);
        }

        $category->update([
            'name' => $dto->name,
            'icon' => $dto->icon,
            'color' => $dto->color,
        ]);

        return $category;
    }

    public function deleteCategory(User $user, int $categoryId): void
    {
        $category = $this->getCategoryById($categoryId);

        if ($category->is_default) {
            throw ValidationException::withMessages([
                'category' => ['Cannot delete default category'],
            ]);
        }

        if ($category->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'category' => ['You do not own this category'],
            ]);
        }

        $category->delete();
    }

    public function getCategoryById(int $categoryId): Category
    {
        return Category::findOrFail($categoryId);
    }
}