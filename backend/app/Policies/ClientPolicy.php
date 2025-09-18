<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class ClientPolicy extends TenantOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return Gate::allows('clients.view') || Gate::allows('clients.manage');
    }

    public function view(User $user, Model $client): bool
    {
        return $this->viewAny($user) && parent::view($user, $client);
    }

    public function create(User $user): bool
    {
        return Gate::allows('clients.create') || Gate::allows('clients.manage');
    }

    public function update(User $user, Model $client): bool
    {
        return (Gate::allows('clients.update') || Gate::allows('clients.manage'))
            && parent::update($user, $client);
    }

    public function delete(User $user, Model $client): bool
    {
        return (Gate::allows('clients.delete') || Gate::allows('clients.manage'))
            && parent::delete($user, $client);
    }

    public function restore(User $user, Client $client): bool
    {
        return $this->update($user, $client);
    }

    public function forceDelete(User $user, Client $client): bool
    {
        return Gate::allows('clients.manage') && parent::delete($user, $client);
    }

    public function archive(User $user, Client $client): bool
    {
        return $this->update($user, $client);
    }

    public function transfer(User $user, Client $client): bool
    {
        return Gate::allows('clients.manage') && parent::update($user, $client);
    }
}
