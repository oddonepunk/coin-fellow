<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'currency',
        'description',
        'invite_code',
    ];

    public function users(): BelongsToMany {
        return $this->members();
    }

    public function expenses(): HasMany {
        return $this->hasMany(Expense::class);
    }

    public function groupUsers(): HasMany {
        return $this->hasMany(GroupUser::class);
    }

    public function members(): BelongsToMany 
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->using(GroupUser::class)
            ->withPivot(['role_id', 'role', 'created_at', 'updated_at'])
            ->withTimestamps();
    }


    public function getOwner(): ?GroupUser 
    {
        return $this->groupUsers()->where('role', 'owner')->first();
    }

    public function getAdmins()
    {
        return $this->groupUsers()
            ->where(function ($query) {
                $query->whereIn('role', ['owner', 'admin'])
                      ->orWhereHas('roleModel', fn($q) => $q->whereIn('name', ['owner', 'admin']));
            })
            ->with('user')
            ->get();
    }


  public function getMembers()
    {
        return $this->groupUsers()
            ->where(function ($query) {
                $query->where('role', 'member')
                      ->orWhereHas('roleModel', fn($q) => $q->where('name', 'member'));
            })
            ->with('user')
            ->get();
    }

    public function scopeWhereUserIsMember($query, User $user)
    {
        return $query->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function scopeWithUserRole($query, User $user)
    {
        return $query->with(['groupUsers' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }]);
    }

   

 
    
    public function isUserAdmin(User $user): bool
    {
        return $this->groupUsers()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->whereIn('role', ['owner', 'admin'])
                      ->orWhereHas('roleModel', fn($q) => $q->whereIn('name', ['owner', 'admin']));
            })
            ->exists();
    }

    public function getUserRole(User $user): ?GroupRole
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->with('roleModel')
            ->first();

        return $groupUser?->roleModel;
    }

    public function getUserRoleName(User $user): ?string {
        return $this->groupUsers()
            ->where('user_id', $user->id)
            ->first()
            ?->roleModel?->name;
    }

    public function userHasPermission(User $user, string $permission): bool
    {
        $groupUser = $this->groupUsers()
            ->where('user_id', $user->id)
            ->with('roleModel')
            ->first();
            
        return $groupUser?->hasPermission($permission) ?? false;
    }

    
    public function isUserOwner(User $user): bool
    {
        return $this->groupUsers()
            ->where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('role', 'owner')
                      ->orWhereHas('roleModel', fn($q) => $q->where('name', 'owner'));
            })
            ->exists();
    }

    public function getMembersCount(): int
    {
        return $this->groupUsers()->count();
    }

    public function getTotalExpenses(): float
    {
        return $this->expenses()->sum('amount');
    }
}
