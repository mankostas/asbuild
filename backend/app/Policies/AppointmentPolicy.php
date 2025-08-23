<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return true;
    }
}
