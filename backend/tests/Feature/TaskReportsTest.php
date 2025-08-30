<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_overview_returns_metrics(): void
    {
        Carbon::setTestNow('2025-01-10');

        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks', 'reports']]);
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.view', 'reports.view'],
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

        $type = TaskType::create(['name' => 'Type', 'tenant_id' => $tenant->id]);
        $status = TaskStatus::create(['slug' => 'done', 'name' => 'Done', 'tenant_id' => $tenant->id]);

        Task::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => $status->slug,
            'assigned_user_id' => $user->id,
            'started_at' => Carbon::now()->subDays(2),
            'completed_at' => Carbon::now()->subDay(),
            'sla_end_at' => Carbon::now()->subDay(),
        ]);

        Task::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => $status->slug,
            'assigned_user_id' => $user->id,
            'started_at' => Carbon::now()->subDays(4),
            'completed_at' => Carbon::now()->subDay(),
            'sla_end_at' => Carbon::now()->subDays(2),
        ]);

        $response = $this->getJson('/api/reports/tasks/overview?type_id=' . $type->id . '&range=7');

        $response->assertStatus(200)->assertJson([
            'throughput' => [
                ['x' => '2025-01-09', 'y' => 2],
            ],
            'cycle_time' => [
                ['x' => '2025-01-09', 'y' => 2880.0],
            ],
            'sla_attainment' => [
                ['x' => '2025-01-09', 'y' => 50.0],
            ],
        ]);
    }
}

