<?php

namespace Tests\Unit\Policies;

use App\Models\TaskType;
use App\Models\Team;
use App\Models\User;
use App\Policies\TaskTypePolicy;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TaskTypePolicyTest extends TestCase
{
    public function test_create_allows_specific_ability(): void
    {
        Gate::shouldReceive('allows')
            ->withAnyArgs()
            ->andReturnUsing(fn (string $ability) => $ability === 'task_types.create');

        $policy = new TaskTypePolicy();

        $this->assertTrue($policy->create(new User()));
    }

    public function test_update_allows_specific_ability(): void
    {
        Gate::shouldReceive('allows')
            ->withAnyArgs()
            ->andReturnUsing(fn (string $ability) => $ability === 'task_types.update');

        $policy = new TaskTypePolicy();
        $user = new User();
        $user->tenant_id = 1;
        $type = new TaskType();
        $type->tenant_id = 1;

        $this->assertTrue($policy->update($user, $type));
    }

    public function test_update_allows_manage_override(): void
    {
        Gate::shouldReceive('allows')
            ->withAnyArgs()
            ->andReturnUsing(fn (string $ability) => $ability === 'task_types.manage');

        $policy = new TaskTypePolicy();
        $user = new User();
        $user->tenant_id = 1;
        $type = new TaskType();
        $type->tenant_id = 1;

        $this->assertTrue($policy->update($user, $type));
    }

    public function test_update_denied_without_permissions(): void
    {
        Gate::shouldReceive('allows')
            ->withAnyArgs()
            ->andReturn(false);

        $policy = new TaskTypePolicy();
        $user = new User();
        $user->tenant_id = 1;
        $type = new TaskType();
        $type->tenant_id = 1;

        $this->assertFalse($policy->update($user, $type));
    }

    public function test_delete_allows_specific_ability(): void
    {
        Gate::shouldReceive('allows')
            ->withAnyArgs()
            ->andReturnUsing(fn (string $ability) => $ability === 'task_types.delete');

        $policy = new TaskTypePolicy();
        $user = new User();
        $user->tenant_id = 1;
        $type = new TaskType();
        $type->tenant_id = 1;

        $this->assertTrue($policy->delete($user, $type));
    }

    public function test_delete_allows_manage_override(): void
    {
        Gate::shouldReceive('allows')
            ->withAnyArgs()
            ->andReturnUsing(fn (string $ability) => $ability === 'task_types.manage');

        $policy = new TaskTypePolicy();
        $user = new User();
        $user->tenant_id = 1;
        $type = new TaskType();
        $type->tenant_id = 1;

        $this->assertTrue($policy->delete($user, $type));
    }

    public function test_delete_denied_without_permissions(): void
    {
        Gate::shouldReceive('allows')
            ->withAnyArgs()
            ->andReturn(false);

        $policy = new TaskTypePolicy();
        $user = new User();
        $user->tenant_id = 1;
        $type = new TaskType();
        $type->tenant_id = 1;

        $this->assertFalse($policy->delete($user, $type));
    }

    public function test_update_returns_false_for_non_task_type(): void
    {
        Gate::shouldReceive('allows')
            ->withAnyArgs()
            ->andReturn(true);

        $policy = new TaskTypePolicy();
        $user = new User();
        $user->tenant_id = 1;
        $team = new Team();
        $team->tenant_id = 1;

        $this->assertFalse($policy->update($user, $team));
    }

    public function test_delete_returns_false_for_non_task_type(): void
    {
        Gate::shouldReceive('allows')
            ->withAnyArgs()
            ->andReturn(true);

        $policy = new TaskTypePolicy();
        $user = new User();
        $user->tenant_id = 1;
        $team = new Team();
        $team->tenant_id = 1;

        $this->assertFalse($policy->delete($user, $team));
    }
}
