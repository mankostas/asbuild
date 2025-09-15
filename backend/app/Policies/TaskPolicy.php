<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class TaskPolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return Gate::allows('tasks.create');
    }

    public function assign(User $user, Task $task): bool
    {
        return Gate::allows('tasks.assign') && $user->tenant_id === $task->tenant_id;
    }

    public function update(User $user, Model $task): bool
    {
        return $task instanceof Task
            && $user->tenant_id === $task->tenant_id
            && (
                Gate::allows('tasks.update')
                || Gate::allows('tasks.manage')
            );
    }
}
