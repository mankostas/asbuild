<?php

namespace App\Policies;

use App\Models\AppointmentType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class AppointmentTypePolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return Gate::allows('types.manage');
    }

    public function update(User $user, AppointmentType $type): bool
    {
        return Gate::allows('types.manage') && parent::update($user, $type);
    }

    public function delete(User $user, AppointmentType $type): bool
    {
        return Gate::allows('types.manage') && parent::delete($user, $type);
    }
}
