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
}
