<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class RolePolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return Gate::allows('roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return Gate::allows('roles.manage') && parent::update($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return Gate::allows('roles.manage') && parent::delete($user, $role);
    }
}
