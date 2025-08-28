<?php

namespace App\Policies;

use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class StatusPolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return Gate::allows('statuses.manage');
    }

    public function update(User $user, Status $status): bool
    {
        return Gate::allows('statuses.manage') && parent::update($user, $status);
    }

    public function delete(User $user, Status $status): bool
    {
        return Gate::allows('statuses.manage') && parent::delete($user, $status);
    }
}
