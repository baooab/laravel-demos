<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'name', 'slug', 'permissions',
    ];
    protected $casts = [
        'permissions' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }

    public function hasAccess($permission)
    {
        return $this->hasPermission($permission);
    }

    private function hasPermission($permission)
    {
        return isset($this->permissions[$permission]) ? $this->permissions[$permission] : false;
    }

}
