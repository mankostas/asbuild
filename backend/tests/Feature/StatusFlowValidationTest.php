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

class StatusFlowValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Task $task;
    protected TaskType $type;

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
            'abilities' => ['tasks.update', 'tasks.status.update'],
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

        $this->type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T',
            'tenant_id' => 1,
            'statuses' => [
                'draft' => [],
                'in_progress' => [],
                'review' => [],
                'completed' => [],
                'rejected' => [],
                'redo' => [],
            ],
            'status_flow_json' => [
                ['draft', 'in_progress'],
                ['in_progress', 'review'],
                ['review', 'completed'],
                ['review', 'redo'],
                ['review', 'rejected'],
                ['redo', 'in_progress'],
            ],
            'require_subtasks_complete' => true,
        ]);

        $this->task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => 1,
            'user_id' => $user->id,
            'task_type_id' => $this->type->id,
            'status' => 'review',
            'assigned_user_id' => $user->id,
        ]);
    }

    public function test_validations_trigger_for_completed(): void
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
            ->assertJson(['code' => 'subtasks_incomplete']);

        $sub->update(['is_completed' => true]);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$this->task->id}/status", ['status' => 'completed'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'completed');
    }

    public function test_validations_skipped_for_redo(): void
    {
        TaskSubtask::create([
            'public_id' => PublicIdGenerator::generate(),
            'task_id' => $this->task->id,
            'title' => 'Req',
            'is_required' => true,
        ]);

        $this->withHeader('X-Tenant-ID', 1)
            ->postJson("/api/tasks/{$this->task->id}/status", ['status' => 'redo'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'redo');
    }
}
