<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AppointmentPolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function assign(User $user, Appointment $appointment): bool
    {
        return Gate::allows('appointments.assign') && $user->tenant_id === $appointment->tenant_id;
    }
}
