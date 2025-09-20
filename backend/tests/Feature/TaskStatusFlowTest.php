<?php

namespace Tests\Feature;

use App\Http\Middleware\Ability;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TaskStatusFlowTest extends TestCase
{
    use RefreshDatabase;

    protected string $tenantPublicId;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'id' => 1, 'name' => 'T', 'features' => ['tasks']
        ]);
        $this->tenantPublicId = $tenant->public_id;
    }

    protected function authUser(): User
    {
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => 1,
            'abilities' => ['tasks.status.update', 'tasks.update'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => 1,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => 1]);
        $user->refresh();
        Sanctum::actingAs($user);
        return $user;
    }

    protected function authManager(): User
    {
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Manager',
            'slug' => 'manager',
            'tenant_id' => 1,
            'abilities' => ['tasks.status.update', 'tasks.update', 'tasks.manage'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'M',
            'email' => 'm@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => 1,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => 1]);
        $user->refresh();
        Sanctum::actingAs($user);
        return $user;
    }

    protected function makeTask(User $user): Task
    {
        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type',
            'tenant_id' => 1,
            'statuses' => ['draft' => [], 'assigned' => [], 'in_progress' => [], 'completed' => []],
            'status_flow_json' => [
                ['draft', 'assigned'],
                ['assigned', 'in_progress'],
                ['in_progress', 'completed'],
            ],
        ]);

        return Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status' => 'draft',
            'status_slug' => \App\Models\TaskStatus::prefixSlug('draft', 1),
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_allows_valid_transition(): void
    {
        $user = $this->authUser();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->postJson("/api/tasks/{$task->public_id}/status", ['status' => 'assigned'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'assigned');
    }

    public function test_rejects_invalid_transition(): void
    {
        $user = $this->authUser();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->postJson("/api/tasks/{$task->public_id}/status", ['status' => 'completed'])
            ->assertStatus(422)
            ->assertJson(['message' => 'invalid_transition']);
    }

    public function test_defaults_when_no_custom_flow(): void
    {
        $user = $this->authUser();
        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type',
            'tenant_id' => 1,
            'statuses' => ['draft' => [], 'assigned' => [], 'in_progress' => [], 'completed' => []],
            'status_flow_json' => null,
        ]);
        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status' => 'draft',
            'status_slug' => \App\Models\TaskStatus::prefixSlug('draft', 1),
            'assigned_user_id' => $user->id,
        ]);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->postJson("/api/tasks/{$task->public_id}/status", ['status' => 'assigned'])
            ->assertStatus(200);
        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->postJson("/api/tasks/{$task->public_id}/status", ['status' => 'completed'])
            ->assertStatus(422);
    }

    public function test_handles_object_edges_in_flow(): void
    {
        $user = $this->authUser();
        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type',
            'tenant_id' => 1,
            'statuses' => [
                ['slug' => 'draft'],
                ['slug' => 'assigned'],
                ['slug' => 'in_progress'],
                ['slug' => 'completed'],
            ],
            'status_flow_json' => [
                [ ['slug' => 'draft'], ['slug' => 'assigned'] ],
                [ ['slug' => 'assigned'], ['slug' => 'in_progress'] ],
                [ ['slug' => 'in_progress'], ['slug' => 'completed'] ],
            ],
        ]);
        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status' => 'draft',
            'assigned_user_id' => $user->id,
        ]);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->postJson("/api/tasks/{$task->public_id}/status", ['status' => 'assigned'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'assigned');
    }

    public function test_update_route_allows_status_change(): void
    {
        $this->withoutMiddleware(Ability::class);
        $user = $this->authUser();
        $task = $this->makeTask($user);

        $this->assertTrue(app(\App\Services\AbilityService::class)->userHasAbility($user, 'tasks.update', 1));

        $response = $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson("/api/tasks/{$task->public_id}", [
                'title' => 'Updated Title',
                'status' => 'assigned',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'assigned');

        $this->assertSame('assigned', $task->fresh()->status);
    }

    public function test_update_route_rejects_invalid_status_transition(): void
    {
        $this->withoutMiddleware(Ability::class);
        $user = $this->authUser();
        $task = $this->makeTask($user);
        $task->update(['title' => 'Original Title']);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson("/api/tasks/{$task->public_id}", [
                'title' => 'Changed Title',
                'status' => 'completed',
            ])
            ->assertStatus(422)
            ->assertJson(['message' => 'invalid_transition']);

        $fresh = $task->fresh();
        $this->assertSame('draft', $fresh->status);
        $this->assertSame('Original Title', $fresh->title);
    }

    public function test_update_route_requires_status_ability_when_status_provided(): void
    {
        $this->withoutMiddleware(Ability::class);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Limited',
            'slug' => 'limited',
            'tenant_id' => 1,
            'abilities' => ['tasks.update'],
            'level' => 1,
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Limited User',
            'email' => 'limited@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => 1,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => 1]);
        $user->refresh();
        Sanctum::actingAs($user);

        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson("/api/tasks/{$task->public_id}", ['status' => 'assigned'])
            ->assertStatus(403);

        $this->assertSame('draft', $task->fresh()->status);

        $this->withHeader('X-Tenant-ID', $this->tenantPublicId)
            ->patchJson("/api/tasks/{$task->public_id}", ['title' => 'Only Title'])
            ->assertStatus(200);
    }
}
