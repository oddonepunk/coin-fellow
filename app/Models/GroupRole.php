<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupRole extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'level',
        'permissions'
    ];

    protected $casts = [
        'permissions' => 'array',
        'level' => 'integer'
    ];

    public function groupUsers(): HasMany {
        return $this->hasMany(GroupUser::class, 'role_id');
    }

    public function hasPermission(string $permission): bool {
        $permissions = $this->permissions ?? [];
        return is_array('*', $permission) || in_array($permission, $permissions);
    }
}
