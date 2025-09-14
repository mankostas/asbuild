<?php

namespace App\Providers;

use App\Models\Manual;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\Team;
use App\Policies\ManualPolicy;
use App\Policies\RolePolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskStatusPolicy;
use App\Policies\TaskTypePolicy;
use App\Policies\TeamPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Task::class => TaskPolicy::class,
        TaskType::class => TaskTypePolicy::class,
        TaskStatus::class => TaskStatusPolicy::class,
        Manual::class => ManualPolicy::class,
        Role::class => RolePolicy::class,
        Team::class => TeamPolicy::class,
    ];

    public function boot(): void
    {
        Gate::define('belongs-to-tenant', function ($user, $tenantId) {
            return $user->tenant_id === $tenantId;
        });

        foreach (config('abilities', []) as $code) {
            Gate::define($code, fn ($user) => $this->hasAbility($user, $code));
        }
    }

    protected function hasAbility($user, string $code): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $tenantId = app()->bound('tenant_id') ? (int) app('tenant_id') : $user->tenant_id;

        $abilities = $user->rolesForTenant($tenantId)
            ->pluck('abilities')
            ->flatten()
            ->filter()
            ->unique()
            ->all();

        if (in_array('*', $abilities)) {
            return true;
        }

        if (in_array($code, $abilities)) {
            return true;
        }

        $prefix = explode('.', $code)[0] . '.manage';

        return in_array($prefix, $abilities);
    }
}
