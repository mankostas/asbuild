<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Password;

trait ManagesTenantUsers
{
    protected function ensureUserIsNotSuperAdmin(User $user): void
    {
        if ($user->hasRole('SuperAdmin')) {
            abort(403, 'Cannot modify a SuperAdmin');
        }
    }

    protected function dispatchPasswordReset(User $user): void
    {
        Password::sendResetLink(['email' => $user->email]);
    }

    protected function dispatchInvite(User $user): void
    {
        $this->dispatchPasswordReset($user);
    }

    protected function resetUserEmail(User $user, string $email): void
    {
        $user->forceFill([
            'email' => $email,
            'email_verified_at' => null,
        ])->save();

        $this->dispatchPasswordReset($user);
    }
}
