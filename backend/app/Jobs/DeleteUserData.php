<?php

namespace App\Jobs;

use App\Models\User;
use App\Support\PublicIdResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteUserData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User|int|string $userIdentifier)
    {
    }

    public function handle(): void
    {
        $user = $this->userIdentifier instanceof User
            ? $this->userIdentifier
            : $this->resolveUser($this->userIdentifier);

        if ($user) {
            $user->delete();
        }
    }

    protected function resolveUser(int|string $identifier): ?User
    {
        $id = app(PublicIdResolver::class)->resolve(User::class, $identifier);

        if ($id === null) {
            return null;
        }

        return User::find($id);
    }
}
