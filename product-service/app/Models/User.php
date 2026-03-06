<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'tenant_id', 'is_active'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_active' => 'boolean'];

    public function hasRole(string $roleName): bool
    {
        return false; // Cross-service role check via User Service
    }

    public function hasPermission(string $permissionName): bool
    {
        return false; // Cross-service permission check via User Service
    }
}
