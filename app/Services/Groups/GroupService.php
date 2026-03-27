<?php

namespace App\Services\Groups;

use App\Models\Group;
use App\Models\User;
use App\Models\GroupUser;
use App\Models\GroupRole;
use App\Services\Notifications\Interfaces\NotificationServiceInterface;
use App\Services\Groups\DTO\CreateGroupDTO;
use App\Services\Groups\DTO\UpdateGroupDTO;
use App\Services\Groups\DTO\InviteUserDTO;
use App\Services\Groups\DTO\GroupStatsDTO;
use App\Services\Groups\DTO\MemberStatsDTO;
use App\Services\Groups\Interfaces\GroupServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class GroupService implements GroupServiceInterface 
{
    public function __construct(
        private NotificationServiceInterface $notificationService
    ) {}

    public function getUserGroups(User $user): LengthAwarePaginator 
    {
        return $user->groups()
            ->withCount('users')
            ->with('users')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    
    public function createGroup(User $user, CreateGroupDTO $dto): Group 
    {
        $group = Group::create([
            'name' => $dto->name,
            'currency' => $dto->currency,
            'description' => $dto->description,
            'invite_code' => Str::random(10),
        ]);

        GroupUser::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        return $group->load('members');
    }

    public function getGroup(User $user, string $groupId): Group 
    {
        $group = Group::with(['users', 'expenses.payer', 'expenses.category'])
            ->findOrFail($groupId);

        if(!$group->groupUsers()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь членом этой группы'],
            ]);
        }

        return $group;
    }

    public function updateGroup(User $user, string $groupId, UpdateGroupDTO $dto): Group 
    {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner', 'admin']);

        if ($dto->name) {
            $group->name = $dto->name;
        }
        if ($dto->currency) {
            $group->currency = $dto->currency;
        }
        if ($dto->description !== null) {
            $group->description = $dto->description;
        }

        $group->save();
        return $group->load('members');
    }

    public function deleteGroup(User $user, string $groupId): void 
    {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner']);

        $group->delete();
    }

    public function inviteUser(User $user, string $groupId, InviteUserDTO $dto): void 
    {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner', 'admin']);

        $invitedUser = User::where('email', $dto->email_or_username)
            ->orWhere('username', $dto->email_or_username)
            ->orWhere('phone', $dto->email_or_username)
            ->first();

        if(!$invitedUser) {
            throw ValidationException::withMessages([
                'email_or_username' => ['Пользователь не найден'],
            ]);
        }

        $existingMembers = GroupUser::where('group_id', $group->id)
            ->where('user_id', $invitedUser->id)
            ->exists();
            
        if($existingMembers) {
            throw ValidationException::withMessages([
                'email_or_username' => ['Пользователь уже находится в группе'],
            ]);
        }

        $role = GroupRole::where('name', $dto->role ?? 'member')->first();

        GroupUser::create([
            'group_id' => $group->id,
            'user_id' => $invitedUser->id,
            'role_id' => $role->id,
            'role' => $dto->role ?? 'member',
        ]);

        $this->notifyInvitation($invitedUser, $user, $group);
    }


    public function removeUser(User $user, string $groupId, string $userId): void
    {
        $group = Group::findOrFail($groupId);

        $this->checkUserPermissions($user, $group, ['owner', 'admin']);

        if ($user->id === $userId) {
            throw ValidationException::withMessages([
                'user' => ['Вы не можете самостоятельно удалиться из группы'],
            ]);
        }

        GroupUser::where('group_id', $group->id)
            ->where('user_id', $userId)
            ->delete();
    }

    public function leaveGroup(User $user, string $groupId): void
    {
        $group = Group::findOrFail($groupId);

        $groupUser = GroupUser::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if($groupUser->role === 'owner') {
            throw ValidationException::withMessages([
                'group' => ['Владелец не может группу. Передайте права или удалите группу'],
            ]);
        }

        $groupUser->delete();
    }

   private function checkUserPermissions(User $user, Group $group, array $allowedRoles): void
   {
        $groupUser = GroupUser::where('group_id', $group->id) 
            ->where('user_id', $user->id)
            ->with('roleModel')
            ->first();

        if (!$groupUser) {
            throw ValidationException::withMessages([
                'permission' => ['У вас нет разрешения на выполнение этого действия'],
            ]);
        }

        if ($groupUser->roleModel && in_array($groupUser->roleModel->name, $allowedRoles)) {
            return;
        }

        if (in_array($groupUser->role, $allowedRoles)) {
            return;
        }

        throw ValidationException::withMessages([
            'permission' => ['У вас нет разрешения на выполнение этого действия'],
        ]);
    }

    private function notifyInvitation(User $invitedUser, User $inviter, Group $group): void
    {
        $this->notificationService->notifyInvitation($invitedUser, [
            'group_id' => $group->id,
            'group_name' => $group->name,
            'inviter_name' => $inviter->first_name ?? $inviter->username,
            'invited_user_id' => $invitedUser->id,
        ]);
    }

    public function getUserRoleInGroup(User $user, Group $group): ?string 
    {
        $groupUser = GroupUser::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();
        
        return $groupUser?->role;
    }

    //функция для передачи прав собственности

    public function transferOwnership(User $currentOwner, string $groupId, string $newOwnerId): void 
    {
        $group = Group::firstOrFail($groupId);

        //проверка, что текущий юзер - смотрящий
        $currentGroupOwner = GroupUser::where('group_id', $group->id)
            ->where('user_id', $currentOwner->id)
            ->where('role', 'owner')
            ->firstOrFail();

        //чекаем, что новый смотрящий находится в группе
        $newOwnerGroupUser = GroupUser::where('group_id', $group->id)
            ->where('user_id', $newOwnerId)
            ->first();

        if(!$newOwnerGroupUser) {
            throw ValidationException::withMessages([
                'user' => ['Пользователь не состоит в группе'],
            ]);
        }

        DB::transaction(function () use ($group, $currentOwner, $newOwnerId) {
            //старый смотрящий в красные переходит
            GroupUser::where('group_id', $group->id)
                ->where('user_id', $currentOwner->id)
                ->update(['role'=> 'admin']);

            GroupUser::where('group_id', $group->id)
                ->where('user_id', $newOwnerId)
                ->update(['role' => 'owner']);
        });
    }


    public function getGroupStats(GroupStatsDTO $dto): array
    {
        $group = Group::with(['expenses', 'expenses.category', 'users'])->findOrFail($dto->groupId);
        
        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        $totalExpenses = $group->expenses()->sum('amount');
        $memberCount = $group->users()->count();
        
        $userExpenses = $group->expenses()
            ->where('payer_id', auth()->id())
            ->sum('amount');
        
        $avgExpensePerMember = $memberCount > 0 ? $totalExpenses / $memberCount : 0;
        
        $topCategories = $group->expenses()
            ->with('category')
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();
        
        $monthlyExpenses = $group->expenses()
            ->select(DB::raw('DATE_FORMAT(date, "%Y-%m") as month'), DB::raw('SUM(amount) as total'))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();
        
        return [
            'total_expenses' => (float) $totalExpenses,
            'member_count' => $memberCount,
            'user_expenses' => (float) $userExpenses,
            'avg_expense_per_member' => (float) $avgExpensePerMember,
            'top_categories' => $topCategories->map(function ($item) use ($totalExpenses) {
                $category = $item->category;
                return [
                    'category_id' => $item->category_id,
                    'category_name' => $category?->name ?? 'Без категории',
                    'category_icon' => $category?->icon ?? '📦',
                    'category_color' => $category?->color ?? '#6B7280',
                    'total' => (float) $item->total,
                    'percentage' => $totalExpenses > 0 ? round(($item->total / $totalExpenses) * 100, 1) : 0
                ];
            }),
            'monthly_expenses' => $monthlyExpenses->map(function ($item) {
                return [
                    'month' => $item->month,
                    'total' => (float) $item->total
                ];
            })->values()
        ];
    }

    public function getMemberStats(MemberStatsDTO $dto): array
    {
        $group = Group::with(['expenses', 'expenses.participants'])->findOrFail($dto->groupId);
        $member = User::findOrFail($dto->userId);
        
        //проверка, что пользователь является участником
        if (!$group->users()->where('user_id', auth()->id())->exists()) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }
        
        //проверка, что запрашиваемый участник состоит в группе
        if (!$group->users()->where('user_id', $dto->userId)->exists()) {
            throw ValidationException::withMessages([
                'user' => ['Пользователь не является участником этой группы'],
            ]);
        }
        
        //сумма, которую юзер заплатил
        $totalPaid = $group->expenses()
            ->where('payer_id', $dto->userId)
            ->sum('amount');
        
        //сумма, которую юзер должен другим
        $totalOwed = 0;
        $participatedExpenses = $group->expenses()
            ->whereHas('participants', function ($q) use ($dto) {
                $q->where('user_id', $dto->userId);
            })
            ->get();
        
        foreach ($participatedExpenses as $expense) {
            $participantShare = $expense->getAmountPerParticipant();
            if ($expense->payer_id !== $dto->userId) {
                $totalOwed += $participantShare;
            }
        }
        
        $balance = $totalPaid - $totalOwed;
        
        return [
            'user_id' => $dto->userId,
            'full_name' => $member->full_name ?? $member->username ?? $member->email,
            'total_paid' => (float) $totalPaid,
            'total_owed' => (float) $totalOwed,
            'balance' => (float) $balance,
            'balance_status' => $balance > 0 ? 'positive' : ($balance < 0 ? 'negative' : 'zero')
        ];
    }
}