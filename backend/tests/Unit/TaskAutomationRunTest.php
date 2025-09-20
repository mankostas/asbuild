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
use App\Support\PublicIdGenerator;

class TaskAutomationRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_skips_rule_with_non_string_status(): void
    {
        Queue::fake();

        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);

        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->getKey(),
        ]);

        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 'draft', 'name' => 'Draft', 'tenant_id' => $tenant->getKey()
        ]);
        TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 'completed', 'name' => 'Completed', 'tenant_id' => $tenant->getKey()
        ]);

        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type',
            'tenant_id' => $tenant->getKey(),
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => [], 'completed' => []],
            'status_flow_json' => [['draft', 'completed']],
        ]);

        $team = Team::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->getKey(), 'name' => 'Team X'
        ]);

        TaskAutomation::create([
            'public_id' => PublicIdGenerator::generate(),
            'task_type_id' => $type->getKey(),
            'event' => 'status_changed',
            'conditions_json' => ['status' => 123],
            'actions_json' => [['type' => 'notify_team', 'team_id' => $team->public_id]],
            'enabled' => true,
        ]);

        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->getKey(),
            'user_id' => $user->getKey(),
            'task_type_id' => $type->getKey(),
            'status_slug' => TaskStatus::prefixSlug('draft', $tenant->getKey()),
            'board_position' => 1,
        ]);

        TaskAutomation::run($task, 'status_changed');

        Queue::assertNothingPushed();
    }
}
