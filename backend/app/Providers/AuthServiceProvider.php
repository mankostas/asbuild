<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Manual;
use App\Policies\AppointmentPolicy;
use App\Policies\ManualPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Appointment::class => AppointmentPolicy::class,
        Manual::class => ManualPolicy::class,
    ];

    public function boot(): void
    {
        Gate::define('belongs-to-tenant', function ($user, $tenantId) {
            return $user->tenant_id === $tenantId;
        });
    }
}
