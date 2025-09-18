<?php

namespace App\Services;

use App\Models\User;
use App\Support\AbilityNormalizer;

class AbilityService
{
    private array $cache = [];

    public function __construct()
    {
        if (function_exists('app')) {
            app()->terminating(fn () => $this->clearCache());
        }
    }

    public function userHasAbility(User $user, string $code, ?int $tenantId = null): bool
    {
        return $this->userHasAnyAbility($user, [$code], $tenantId);
    }

    public function userHasAnyAbility(User $user, array $codes, ?int $tenantId = null): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $abilities = $this->resolveAbilities($user, $tenantId);

        $codes = AbilityNormalizer::expandRequestedAbilities($codes);

        if (in_array('*', $abilities, true)) {
            return true;
        }

        foreach ($codes as $code) {
            if ($this->abilityMatches($code, $abilities)) {
                return true;
            }
        }

        return false;
    }

    public function resolveAbilities(User $user, ?int $tenantId = null): array
    {
        $tenantId = $this->resolveTenantId($user, $tenantId);

        $cacheKey = $this->resolveCacheKey($user, $tenantId);

        if (array_key_exists($cacheKey, $this->cache)) {
            return $this->cache[$cacheKey];
        }

        $roles = $user->roles()->wherePivotNull('tenant_id')->get();

        if ($tenantId !== null) {
            $roles = $user->rolesForTenant($tenantId)->merge($roles);
        }

        $abilities = $roles->pluck('abilities')->flatten()->filter()->unique()->values()->all();
        $abilities = AbilityNormalizer::normalizeList($abilities);

        return $this->cache[$cacheKey] = $abilities;
    }

    protected function resolveTenantId(User $user, ?int $tenantId = null): ?int
    {
        if ($tenantId !== null) {
            return $tenantId;
        }

        if (app()->bound('tenant_id')) {
            $boundTenantId = app('tenant_id');

            return $boundTenantId !== null ? (int) $boundTenantId : null;
        }

        return $user->tenant_id !== null ? (int) $user->tenant_id : null;
    }

    protected function abilityMatches(string $code, array $abilities): bool
    {
        if (in_array($code, $abilities, true)) {
            return true;
        }

        $parts = explode('.', $code);

        if (count($parts) >= 2) {
            $clientParts = array_merge([$parts[0], 'client'], array_slice($parts, 1));
            $clientAbility = implode('.', $clientParts);

            if (in_array($clientAbility, $abilities, true)) {
                return true;
            }
        }

        $prefix = $parts[0] . '.manage';

        return in_array($prefix, $abilities, true);
    }

    private function clearCache(): void
    {
        $this->cache = [];
    }

    private function resolveCacheKey(User $user, ?int $tenantId): string
    {
        $userKey = $user->getKey();

        if ($userKey === null) {
            $userKey = spl_object_hash($user);
        }

        $tenantKey = $tenantId !== null ? (string) $tenantId : 'null';

        return $userKey . '|' . $tenantKey;
    }
}
