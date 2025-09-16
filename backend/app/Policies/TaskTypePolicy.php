<?php

namespace App\Policies;

use App\Models\TaskType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class TaskTypePolicy extends TenantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('task_types.view') || Gate::allows('task_types.manage');
    }

    public function view(User $user, Model $type): bool
    {
        if (! $type instanceof TaskType) {
            return false;
        }

        return $this->viewAny($user) && parent::view($user, $type);
    }

    public function create(User $user): bool
    {
        return Gate::allows('task_types.create') || Gate::allows('task_types.manage');
    }

    public function update(User $user, Model $type): bool
    {
        if (! $type instanceof TaskType) {
            return false;
        }

        return (Gate::allows('task_types.update') || Gate::allows('task_types.manage'))
            && parent::update($user, $type);
    }

    public function delete(User $user, Model $type): bool
    {
        if (! $type instanceof TaskType) {
            return false;
        }

        return (Gate::allows('task_types.delete') || Gate::allows('task_types.manage'))
            && parent::delete($user, $type);
    }

    public function validate(User $user): bool
    {
        return Gate::allows('task_types.create') || Gate::allows('task_types.manage');
    }
}
