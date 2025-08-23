<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Manual;

class ManualPolicy extends TenantOwnedPolicy
{
    public function create(User $user): bool
    {
        return true;
    }
}
