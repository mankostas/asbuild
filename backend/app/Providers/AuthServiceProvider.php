<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Manual;
use App\Models\Role;
use App\Models\Status;
use App\Models\Team;
use App\Policies\AppointmentPolicy;
use App\Policies\AppointmentTypePolicy;
use App\Policies\ManualPolicy;
use App\Policies\RolePolicy;
use App\Policies\StatusPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Appointment::class => AppointmentPolicy::class,
        AppointmentType::class => AppointmentTypePolicy::class,
        Manual::class => ManualPolicy::class,
        Role::class => RolePolicy::class,
        Status::class => StatusPolicy::class,
        Team::class => TeamPolicy::class,
    ];

    public function boot(): void
    {
        Gate::define('belongs-to-tenant', function ($user, $tenantId) {
            return $user->tenant_id === $tenantId;
        });

        Gate::define('roles.manage', fn ($user) => $this->hasAbility($user, 'roles.manage'));
        Gate::define('teams.manage', fn ($user) => $this->hasAbility($user, 'teams.manage'));
        Gate::define('appointments.assign', fn ($user) => $this->hasAbility($user, 'appointments.assign'));
        Gate::define('types.manage', fn ($user) => $this->hasAbility($user, 'types.manage'));
        Gate::define('statuses.manage', fn ($user) => $this->hasAbility($user, 'statuses.manage'));
    }

    protected function hasAbility($user, string $code): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $tenantId = app()->bound('tenant_id') ? (int) app('tenant_id') : $user->tenant_id;

        $roles = $user->rolesForTenant($tenantId)
            ->merge($user->roles()->wherePivotNull('tenant_id')->get());

        $abilities = $roles->pluck('abilities')->flatten()->filter()->unique()->all();

        return in_array($code, $abilities);
    }
}
