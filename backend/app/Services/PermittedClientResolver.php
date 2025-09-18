<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PermittedClientResolver
{
    private array $cache = [];

    public function __construct(private AbilityService $abilityService)
    {
    }

    public function resolve(User $user): ?array
    {
        $key = $this->cacheKey($user);

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        if ($user->isSuperAdmin()) {
            return $this->cache[$key] = null;
        }

        $tenantId = $user->tenant_id;
        if ($tenantId === null) {
            return $this->cache[$key] = [];
        }

        $query = Client::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);

                if ($this->hasClientMembershipPivot()) {
                    $q->orWhereExists(function ($sub) use ($user) {
                        $sub->selectRaw('1')
                            ->from('client_user')
                            ->whereColumn('client_user.client_id', 'clients.id')
                            ->where('client_user.user_id', $user->id);
                    });
                }
            });

        $ids = $query->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        return $this->cache[$key] = $ids;
    }

    public function shouldRestrictTasks(User $user): bool
    {
        return $this->shouldRestrictFeature($user, 'tasks');
    }

    public function shouldRestrictReports(User $user): bool
    {
        return $this->shouldRestrictFeature($user, 'reports');
    }

    protected function shouldRestrictFeature(User $user, string $feature): bool
    {
        if ($user->isSuperAdmin()) {
            return false;
        }

        $abilities = $this->abilityService->resolveAbilities($user);
        $featurePrefix = $feature . '.';

        $hasFeatureAbility = false;
        foreach ($abilities as $ability) {
            if (! is_string($ability) || ! Str::startsWith($ability, $featurePrefix)) {
                continue;
            }

            $hasFeatureAbility = true;

            if (! Str::contains($ability, '.client.')) {
                return false;
            }
        }

        if (! $hasFeatureAbility) {
            return false;
        }

        $permitted = $this->resolve($user);

        return is_array($permitted);
    }

    private function hasClientMembershipPivot(): bool
    {
        static $checked;

        if ($checked !== null) {
            return $checked;
        }

        return $checked = Schema::hasTable('client_user');
    }

    private function cacheKey(User $user): string
    {
        $key = $user->getKey();

        if ($key === null) {
            $key = spl_object_hash($user);
        }

        return (string) $key;
    }
}
