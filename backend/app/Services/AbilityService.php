<?php

namespace App\Services;

use App\Models\User;

class AbilityService
{
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

        $roles = $user->rolesForTenant($tenantId)
            ->merge($user->roles()->wherePivotNull('tenant_id')->get());

        return $roles->pluck('abilities')->flatten()->filter()->unique()->values()->all();
    }

    protected function resolveTenantId(User $user, ?int $tenantId = null): int
    {
        if ($tenantId !== null) {
            return $tenantId;
        }

        if (app()->bound('tenant_id')) {
            return (int) app('tenant_id');
        }

        return (int) $user->tenant_id;
    }

    protected function abilityMatches(string $code, array $abilities): bool
    {
        if (in_array($code, $abilities, true)) {
            return true;
        }

        $prefix = explode('.', $code)[0] . '.manage';

        return in_array($prefix, $abilities, true);
    }
}
