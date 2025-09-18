<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Manual;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\Team;
use App\Policies\ClientPolicy;
use App\Policies\ManualPolicy;
use App\Policies\RolePolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskStatusPolicy;
use App\Policies\TaskTypePolicy;
use App\Policies\TeamPolicy;
use App\Services\AbilityService;
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
        Client::class => ClientPolicy::class,
    ];

    public function boot(AbilityService $abilityService): void
    {
        Gate::define('belongs-to-tenant', function ($user, $tenantId) {
            return $user->tenant_id === $tenantId;
        });

        foreach (config('abilities', []) as $code) {
            Gate::define($code, fn ($user) => $abilityService->userHasAbility($user, $code));
        }
    }
}
