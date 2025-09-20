<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TaskSubtaskTest extends TestCase
{
    use RefreshDatabase;

    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'id' => 1, 'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => 1,
            'abilities' => ['tasks.update', 'tasks.status.update', 'tasks.manage'],
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
        Sanctum::actingAs($user);
        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T',
            'tenant_id' => 1,
            'statuses' => ['draft' => [], 'assigned' => [], 'in_progress' => [], 'completed' => []],
            'status_flow_json' => [
                ['draft', 'assigned'],
                ['assigned', 'in_progress'],
                ['in_progress', 'completed'],
            ],
            'require_subtasks_complete' => true,
        ]);
        $this->task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status' => 'in_progress',
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_crud_and_reorder_subtasks(): void
    {
        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$this->task->id}/subtasks", ['title' => 'A'])
            ->assertStatus(201)
            ->assertJsonPath('title', 'A');
        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$this->task->id}/subtasks", ['title' => 'B'])
            ->assertStatus(201);
        $s1 = TaskSubtask::where('title', 'A')->first();
        $s2 = TaskSubtask::where('title', 'B')->first();

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson("/api/tasks/{$this->task->id}/subtasks/{$s1->id}", ['title' => 'A1', 'is_completed' => true])
            ->assertStatus(200)
            ->assertJsonPath('title', 'A1');

        $this->withHeader('X-Tenant-ID', 1)
            ->patchJson("/api/tasks/{$this->task->id}/subtasks/reorder", ['order' => [$s2->id, $s1->id]])
            ->assertStatus(200);
        $this->assertEquals(1, $s2->fresh()->position);
        $this->assertEquals(2, $s1->fresh()->position);

        $this->withHeader('X-Tenant-ID', 1)
            ->deleteJson("/api/tasks/{$this->task->id}/subtasks/{$s1->id}")
            ->assertStatus(200);
        $this->assertDatabaseMissing('task_subtasks', ['id' => $s1->id]);
    }

    public function test_status_constraint_blocks_incomplete_subtasks(): void
    {
        $sub = TaskSubtask::create([
            'public_id' => PublicIdGenerator::generate(),
            'task_id' => $this->task->id,
            'title' => 'Req',
            'is_required' => true,
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$this->task->id}/status", ['status' => 'completed'])
            ->assertStatus(422)
            ->assertJson(['reason' => 'subtasks_incomplete']);

        $sub->update(['is_completed' => true]);
        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$this->task->id}/status", ['status' => 'completed'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }
}
