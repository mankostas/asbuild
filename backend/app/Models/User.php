<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'phone',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = ['roles'];

    protected $casts = [
        'theme_settings' => 'array',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot('tenant_id')->withTimestamps();
    }

    public function rolesForTenant(int $tenantId)
    {
        return $this->roles()->wherePivot('tenant_id', $tenantId)->get();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()
            ->whereNull('roles.tenant_id')
            ->where('slug', 'super_admin')
            ->exists();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }
}
