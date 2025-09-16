<?php

namespace App\Policies;

use App\Models\Manual;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ManualPolicy extends TenantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('manuals.view');
    }

    public function view(User $user, Manual $manual): bool
    {
        return Gate::allows('manuals.view') && parent::view($user, $manual);
    }

    public function create(User $user): bool
    {
        return Gate::allows('manuals.manage');
    }

    public function update(User $user, Manual $manual): bool
    {
        return Gate::allows('manuals.manage') && parent::update($user, $manual);
    }

    public function delete(User $user, Manual $manual): bool
    {
        return Gate::allows('manuals.manage') && parent::delete($user, $manual);
    }
}
