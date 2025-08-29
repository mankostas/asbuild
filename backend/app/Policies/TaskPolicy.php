<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TaskPolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function assign(User $user, Task $task): bool
    {
        return Gate::allows('tasks.assign') && $user->tenant_id === $task->tenant_id;
    }
}
