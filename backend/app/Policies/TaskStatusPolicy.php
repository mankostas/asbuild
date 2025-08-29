<?php

namespace App\Policies;

use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TaskStatusPolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return Gate::allows('task_statuses.manage');
    }

    public function update(User $user, TaskStatus $status): bool
    {
        return Gate::allows('task_statuses.manage') && parent::update($user, $status);
    }

    public function delete(User $user, TaskStatus $status): bool
    {
        return Gate::allows('task_statuses.manage') && parent::delete($user, $status);
    }
}
