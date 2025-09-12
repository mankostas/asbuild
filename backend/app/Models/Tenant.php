<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\App;
use App\Models\Task;

class Tenant extends Model
{
    protected static ?Tenant $current = null;
    protected $fillable = [
        'name',
        'quota_storage_mb',
        'features',
        'feature_abilities',
        'phone',
        'address',
    ];

    protected $casts = [
        'features' => 'array',
        'feature_abilities' => 'array',
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
            $tenant->roles()->create([
                'name' => 'ClientAdmin',
                'slug' => 'client_admin',
                'level' => 1,
                'abilities' => [],
            ]);
        });
    }

    public function selectedFeatureAbilities(): array
    {
        $map = config('feature_map', []);
        $selected = $this->feature_abilities;

        if ($selected === null) {
            $selected = [];
            foreach ($this->features ?? [] as $feature) {
                $selected[$feature] = $map[$feature]['abilities'] ?? [];
            }
        }

        return $selected;
    }

    public function allowedAbilities(): array
    {
        $map = config('feature_map', []);
        $abilities = [];
        $selected = $this->selectedFeatureAbilities();

        foreach ($this->features ?? [] as $feature) {
            $featureAbilities = $map[$feature]['abilities'] ?? [];
            $chosen = $selected[$feature] ?? $featureAbilities;
            $abilities = array_merge(
                $abilities,
                array_intersect($featureAbilities, $chosen)
            );
        }

        return array_values(array_unique($abilities));
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
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
