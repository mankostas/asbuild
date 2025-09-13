<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TaskType;
use App\Models\TaskStatus;
use App\Models\Team;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Jobs\AutomationNotifyTeamJob;

class TaskAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_completed_notifies_team(): void
    {
        Queue::fake();
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_automations.manage', 'tasks.update', 'tasks.manage'],
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        TaskStatus::create(['slug' => 'draft', 'name' => 'Draft', 'tenant_id' => $tenant->id]);
        TaskStatus::create(['slug' => 'completed', 'name' => 'Completed', 'tenant_id' => $tenant->id]);

        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => [], 'completed' => []],
            'status_flow_json' => [ ['draft', 'completed'] ],
        ]);

        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Team X']);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/task-types/{$type->id}/automations", [
                'event' => 'status_changed',
                'conditions_json' => ['status' => 'completed'],
                'actions_json' => [['type' => 'notify_team', 'team_id' => $team->id]],
                'enabled' => true,
            ])->assertCreated();

        $task = Task::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => TaskStatus::prefixSlug('draft', $tenant->id),
            'board_position' => 1,
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->patchJson('/api/task-board/move', [
                'task_id' => $task->id,
                'status_slug' => 'completed',
                'index' => 0,
            ])->assertOk();

        Queue::assertPushed(AutomationNotifyTeamJob::class, function ($job) use ($task, $team) {
            return $job->taskId === $task->id && $job->teamId === $team->id;
        });
    }
}
