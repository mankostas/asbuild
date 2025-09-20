<?php

namespace App\Models;

use App\Models\Client;
use App\Models\Concerns\HasPublicId;
use App\Models\Task;
use App\Support\AbilityNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;

class Tenant extends Model
{
    use SoftDeletes;
    use HasPublicId;

    protected static ?Tenant $current = null;
    protected $fillable = [
        'public_id',
        'name',
        'quota_storage_mb',
        'features',
        'feature_abilities',
        'phone',
        'address',
        'status',
        'archived_at',
    ];

    protected $casts = [
        'public_id' => 'string',
        'features' => 'array',
        'feature_abilities' => 'array',
        'archived_at' => 'datetime',
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

        return AbilityNormalizer::normalizeFeatureAbilityMap($selected);
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

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
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
