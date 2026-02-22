<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupUser extends Pivot
{
    protected $table = 'group_user';

    protected $fillable = [
        'group_id',
        'user_id',
        'role_id',
        'role'
    ];

    protected $casts = [
        'user_id' => 'string',
    ];

    public function group() 
    {
        return $this->belongsTo(Group::class);
    }

    public function user() 
    {
        return $this->belongsTo(User::class);
    }

    //связь со сущность group_role
    public function roleModel(): BelongsTo 
    {
        return $this->belongsTo(GroupRole::class, 'role_id');
    }

   
    public function isOwner(): bool 
    {
        return $this->roleModel?->name === 'owner' || $this->role === 'owner';
    }

    public function isAdmin(): bool 
    {
        return $this->roleModel?->name === 'admin' || $this->role === 'admin';
    }

    public function hasPermission(string $permission): bool 
    {
        return $this->roleModel?->hasPermission($permission) ?? false;
    }
}
