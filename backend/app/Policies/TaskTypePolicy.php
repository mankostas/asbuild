<?php

namespace App\Policies;

use App\Models\TaskType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TaskTypePolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return Gate::allows('task_types.manage');
    }

    public function update(User $user, TaskType $type): bool
    {
        return Gate::allows('task_types.manage') && parent::update($user, $type);
    }

    public function delete(User $user, TaskType $type): bool
    {
        return Gate::allows('task_types.manage') && parent::delete($user, $type);
    }

    public function validate(User $user): bool
    {
        return Gate::allows('task_types.create') || Gate::allows('task_types.manage');
    }
}
