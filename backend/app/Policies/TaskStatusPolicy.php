<?php

namespace App\Policies;

use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class TaskStatusPolicy extends TenantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('task_statuses.view') || Gate::allows('task_statuses.manage');
    }

    public function view(User $user, Model $status): bool
    {
        if (! $status instanceof TaskStatus) {
            return false;
        }

        if ($status->tenant_id === null) {
            return $this->viewAny($user);
        }

        return $this->viewAny($user) && parent::view($user, $status);
    }

    public function create(User $user): bool
    {
        return Gate::allows('task_statuses.manage');
    }

    public function update(User $user, Model $status): bool
    {
        if (! $status instanceof TaskStatus) {
            return false;
        }

        return Gate::allows('task_statuses.manage') && parent::update($user, $status);
    }

    public function delete(User $user, Model $status): bool
    {
        if (! $status instanceof TaskStatus) {
            return false;
        }

        return Gate::allows('task_statuses.manage') && parent::delete($user, $status);
    }
}
