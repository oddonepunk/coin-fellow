<?php

namespace App\Services\Users;

use App\Models\User;
use App\Models\GroupUser;
use App\Services\Users\DTO\SearchUsersDTO;
use App\Services\Users\Interfaces\UserServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class UserService implements UserServiceInterface
{
    public function searchUsers(SearchUsersDTO $dto): LengthAwarePaginator
    {
        $query = User::query();

        //Поиск по нескольким полям
        $searchTerm = '%' . $dto->query . '%';
        $query->where(function ($q) use ($searchTerm) {
            $q->where('first_name', 'like', $searchTerm)
              ->orWhere('last_name', 'like', $searchTerm)
              ->orWhere('username', 'like', $searchTerm)
              ->orWhere('email', 'like', $searchTerm)
              ->orWhere('phone', 'like', $searchTerm);
        });

        //Исключаем текущего пользователя
        $query->where('id', '!=', auth()->id());

        //Исключаем пользователей уже в группе
        if ($dto->excludeGroupId) {
            $query->whereDoesntHave('groups', function ($q) use ($dto) {
                $q->where('group_id', $dto->excludeGroupId);
            });
        }

        return $query->paginate($dto->limit);
    }

    public function getUserById(string $userId): User
    {
        return User::findOrFail($userId);
    }

    public function getUsersForInvite(string $groupId, string $query): array
    {
        $dto = new SearchUsersDTO(
            query: $query,
            excludeGroupId: $groupId,
            limit: 10
        );

        $users = $this->searchUsers($dto);
        
        return $users->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'full_name' => $this->getFullName($user),
                'initials' => $this->getInitials($user),
                'display_name' => $this->getDisplayName($user)
            ];
        })->toArray();
    }

    private function getFullName(User $user): string
    {
        if ($user->first_name && $user->last_name) {
            return $user->first_name . ' ' . $user->last_name;
        }
        if ($user->first_name) {
            return $user->first_name;
        }
        if ($user->username) {
            return $user->username;
        }
        return $user->email;
    }

    private function getInitials(User $user): string
    {
        if ($user->first_name && $user->last_name) {
            return strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));
        }
        if ($user->first_name) {
            return strtoupper(substr($user->first_name, 0, 2));
        }
        if ($user->username) {
            return strtoupper(substr($user->username, 0, 2));
        }
        if ($user->email) {
            return strtoupper(substr($user->email, 0, 2));
        }
        return 'U';
    }

    private function getDisplayName(User $user): string
    {
        if ($user->first_name && $user->last_name) {
            return $user->first_name . ' ' . $user->last_name;
        }
        if ($user->first_name) {
            return $user->first_name;
        }
        if ($user->username) {
            return '@' . $user->username;
        }
        return $user->email;
    }
}