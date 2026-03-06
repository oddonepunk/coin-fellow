<?php

namespace App\Services\Categories\Interfaces;

use App\Models\Category;
use App\Models\User;
use App\Services\Categories\DTO\CategoryDTO;
use Illuminate\Support\Collection;

interface CategoryServiceInterface
{
    public function getAllCategories(User $user): Collection;
    public function getDefaultCategories(): Collection;
    public function getUserCategories(User $user): Collection;
    public function createCategory(User $user, CategoryDTO $dto): Category;
    public function updateCategory(User $user, int $categoryId, CategoryDTO $dto): Category;
    public function deleteCategory(User $user, int $categoryId): void;
    public function getCategoryById(int $categoryId): Category;
}