<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class RolePolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return Gate::allows('roles.manage');
    }

    public function view(User $user, Model $role): bool
    {
        return $role instanceof Role
            && (Gate::allows('roles.view') || Gate::allows('roles.manage'))
            && parent::view($user, $role);
    }

    public function update(User $user, Model $role): bool
    {
        return $role instanceof Role
            && Gate::allows('roles.manage')
            && parent::update($user, $role);
    }

    public function delete(User $user, Model $role): bool
    {
        return $role instanceof Role
            && Gate::allows('roles.manage')
            && parent::delete($user, $role);
    }
}
