<?php

namespace App\Policies;

use App\Models\TaskTypeVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class TaskTypeVersionPolicy extends TenantOwnedPolicy
{
    public function view(User $user, Model $model): bool
    {
        /** @var TaskTypeVersion $model */
        return $user->tenant_id === $model->taskType->tenant_id;
    }

    public function manage(User $user, TaskTypeVersion $version): bool
    {
        return Gate::allows('task_type_versions.manage') && $this->view($user, $version);
    }
}

