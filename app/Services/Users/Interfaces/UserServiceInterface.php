<?php

namespace App\Services\Users\Interfaces;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Services\Users\DTO\SearchUsersDTO;

interface UserServiceInterface
{
    public function searchUsers(SearchUsersDTO $dto): LengthAwarePaginator;
    public function getUserById(string $userId): User;
    public function getUsersForInvite(string $groupId, string $query): array;
}