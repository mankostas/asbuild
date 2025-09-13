<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\TaskAutomation;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TaskAutomationRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_skips_rule_with_non_string_status(): void
    {
        Queue::fake();

        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);

        $user = User::create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);

        TaskStatus::create(['slug' => 'draft', 'name' => 'Draft', 'tenant_id' => $tenant->id]);
        TaskStatus::create(['slug' => 'completed', 'name' => 'Completed', 'tenant_id' => $tenant->id]);

        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => [], 'completed' => []],
            'status_flow_json' => [['draft', 'completed']],
        ]);

        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Team X']);

        TaskAutomation::create([
            'task_type_id' => $type->id,
            'event' => 'status_changed',
            'conditions_json' => ['status' => 123],
            'actions_json' => [['type' => 'notify_team', 'team_id' => $team->id]],
            'enabled' => true,
        ]);

        $task = Task::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => TaskStatus::prefixSlug('draft', $tenant->id),
            'board_position' => 1,
        ]);

        TaskAutomation::run($task, 'status_changed');

        Queue::assertNothingPushed();
    }
}
