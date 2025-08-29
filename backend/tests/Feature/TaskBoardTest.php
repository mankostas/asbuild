<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BoardPositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::create(['id' => 1, 'name' => 'T', 'features' => ['tasks']]);
        TaskStatus::insert([
            ['slug' => 'draft', 'name' => 'Draft'],
            ['slug' => 'assigned', 'name' => 'Assigned'],
        ]);
    }

    protected function authUser(): User
    {
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => 1,
            'abilities' => ['tasks.view', 'tasks.update'],
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => 1,
            'phone' => '123',
            'address' => 'Street',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => 1]);
        Sanctum::actingAs($user);
        return $user;
    }

    protected function makeTask(User $user): Task
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => 1,
            'statuses' => ['draft' => [], 'assigned' => []],
            'status_flow_json' => [
                ['draft', 'assigned'],
            ],
        ]);

        return Task::create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => 'draft',
            'board_position' => 1000,
        ]);
    }

    public function test_move_valid(): void
    {
        $user = $this->authUser();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', [
                'task_id' => $task->id,
                'status_slug' => 'assigned',
                'index' => 0,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status_slug', 'assigned');
    }

    public function test_move_invalid_transition(): void
    {
        $user = $this->authUser();
        $task = $this->makeTask($user);

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson('/api/task-board/move', [
                'task_id' => $task->id,
                'status_slug' => 'draft',
                'index' => 0,
            ])
            ->assertStatus(422);
    }
}
