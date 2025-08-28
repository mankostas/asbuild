<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;

class Tenant extends Model
{
    protected static ?Tenant $current = null;
    protected $fillable = [
        'name',
        'quota_storage_mb',
        'features',
        'phone',
        'address',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (self $tenant): void {
            $tenant->roles()->create([
                'name' => 'Tenant',
                'slug' => 'tenant',
                'level' => 1,
                'abilities' => [],
            ]);
        });
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public static function current(): ?Tenant
    {
        if (static::$current) {
            return static::$current;
        }

        if (App::bound('tenant_id')) {
            return static::$current = static::find(App::get('tenant_id'));
        }

        return null;
    }

    public static function setCurrent(?Tenant $tenant): void
    {
        static::$current = $tenant;
        if ($tenant) {
            App::instance('tenant_id', $tenant->id);
        }
    }
}
