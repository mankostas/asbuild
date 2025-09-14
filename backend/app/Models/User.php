<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
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
        'type',
        'department',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = ['roles'];

    protected $casts = [
        'theme_settings' => 'array',
        'type' => 'string',
        'last_login_at' => 'datetime',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot('tenant_id')->withTimestamps();
    }

    public function rolesForTenant(int $tenantId)
    {
        return $this->roles()->wherePivot('tenant_id', $tenantId)->get();
    }

    /**
     * Determine if the user holds the SuperAdmin role.
     *
     * This helper can be reused across policies and middleware to
     * allow privileged users to bypass tenant restrictions.
     */
    public function isSuperAdmin(): bool
    {
        return $this->roles()
            ->where(function ($q) {
                $q->where('slug', 'super_admin')
                    ->orWhere('name', 'SuperAdmin');
            })
            ->exists();
    }

    public function hasRole(string $role): bool
    {
        $slug = Str::snake($role);

        return $this->roles()
            ->where(function ($q) use ($role, $slug) {
                $q->where('name', $role)->orWhere('slug', $slug);
            })
            ->exists();
    }

    public function roleLevel(?int $tenantId = null): int
    {
        $tenantId = $tenantId ?? $this->tenant_id;

        $roles = $this->rolesForTenant($tenantId)
            ->merge($this->roles()->wherePivotNull('tenant_id')->get());

        return $roles->min('level') ?? PHP_INT_MAX;
    }
}
