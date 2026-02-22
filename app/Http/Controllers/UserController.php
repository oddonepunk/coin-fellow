<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\SearchUsersRequest;
use App\Http\Resources\UserSearchResource;
use App\Services\Users\DTO\SearchUsersDTO;
use App\Services\Users\Interfaces\UserServiceInterface;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private UserServiceInterface $userService
    ) {}

    public function search(SearchUsersRequest $request): JsonResponse
    {
        $dto = SearchUsersDTO::from([
            'query' => $request->query('query'),
            'excludeGroupId' => $request->query('group_id'),
            'limit' => $request->query('limit', 10)
        ]);

        $users = $this->userService->searchUsers($dto);

        return response()->json([
            'success' => true,
            'data' => UserSearchResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total()
            ],
            'message' => 'Users retrieved successfully'
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        return response()->json([
            'success' => true,
            'data' => new UserSearchResource($user),
            'message' => 'User retrieved successfully'
        ]);
    }

    public function forInvite(SearchUsersRequest $request, string $groupId): JsonResponse
    {
        $users = $this->userService->getUsersForInvite(
            $groupId,
            $request->query('query', '')
        );

        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Users for invite retrieved successfully'
        ]);
    }
}