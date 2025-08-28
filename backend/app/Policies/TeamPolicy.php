<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TeamPolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return Gate::allows('teams.manage');
    }

    public function update(User $user, Team $team): bool
    {
        return Gate::allows('teams.manage') && parent::update($user, $team);
    }

    public function delete(User $user, Team $team): bool
    {
        return Gate::allows('teams.manage') && parent::delete($user, $team);
    }
}
