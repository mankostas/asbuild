<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class TeamPolicy extends TenantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('teams.view');
    }

    public function view(User $user, Model $team): bool
    {
        return Gate::allows('teams.view') && parent::view($user, $team);
    }

    public function create(User $user): bool
    {
        return Gate::allows('teams.create');
    }

    public function update(User $user, Model $team): bool
    {
        return Gate::allows('teams.update') && parent::update($user, $team);
    }

    public function delete(User $user, Model $team): bool
    {
        return Gate::allows('teams.delete') && parent::delete($user, $team);
    }
}
