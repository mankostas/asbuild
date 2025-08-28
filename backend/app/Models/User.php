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
        $token = $this->currentAccessToken();
        if ($token && str_starts_with($token->name, 'impersonation')) {
            return true;
        }

        return $this->roles()
            ->where(function ($q) {
                $q->where('slug', 'super_admin')
                    ->orWhere('name', 'SuperAdmin');
            })
            ->exists();
    }

    public function hasRole(string $role): bool
    {
        $token = $this->currentAccessToken();
        if ($token && str_starts_with($token->name, 'impersonation')) {
            return true;
        }

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
