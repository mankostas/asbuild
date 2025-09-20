<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\File;
use App\Models\Manual;
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
        $status = TaskStatus::create(['slug' => 'completed', 'name' => 'Completed', 'tenant_id' => $tenant->id]);

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

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/reports/tasks/overview?type_id=' . $type->id . '&range=7');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'x' => '2025-01-09',
            'y' => 2,
        ]);
        $response->assertJsonFragment([
            'x' => '2025-01-09',
            'y' => 2880,
        ]);
        $response->assertJsonPath('sla_attainment.0.x', '2025-01-09');
        $response->assertJsonPath('sla_attainment.0.y', 0);
    }

    public function test_materials_report_filters_by_client(): void
    {
        Carbon::setTestNow('2025-01-10 12:00:00');

        $tenant = Tenant::create(['name' => 'T', 'features' => ['reports']]);

        $restrictedRole = Role::create([
            'name' => 'Restricted Reporter',
            'slug' => 'restricted_reporter',
            'tenant_id' => $tenant->id,
            'abilities' => ['reports.client.view'],
            'level' => 1,
        ]);

        $generalRole = Role::create([
            'name' => 'Reporter',
            'slug' => 'reporter',
            'tenant_id' => $tenant->id,
            'abilities' => ['reports.view'],
            'level' => 1,
        ]);

        $restrictedUser = User::create([
            'name' => 'Restricted',
            'email' => 'restricted@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $restrictedUser->roles()->attach($restrictedRole->id, ['tenant_id' => $tenant->id]);

        $generalUser = User::create([
            'name' => 'General',
            'email' => 'general@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '1234567',
            'address' => 'Street 2',
        ]);
        $generalUser->roles()->attach($generalRole->id, ['tenant_id' => $tenant->id]);

        $clientA = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Client A',
        ]);
        $clientA->user_id = $restrictedUser->id;
        $clientA->save();

        $clientB = Client::create([
            'tenant_id' => $tenant->id,
            'name' => 'Client B',
        ]);

        $fileA = File::create([
            'path' => 'manuals/a.pdf',
            'filename' => 'a.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        $fileB = File::create([
            'path' => 'manuals/b.pdf',
            'filename' => 'b.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
        ]);

        Manual::create([
            'tenant_id' => $tenant->id,
            'file_id' => $fileA->id,
            'category' => 'Safety',
            'tags' => [],
            'client_id' => $clientA->id,
        ]);

        Manual::create([
            'tenant_id' => $tenant->id,
            'file_id' => $fileB->id,
            'category' => 'Compliance',
            'tags' => [],
            'client_id' => $clientB->id,
        ]);

        Sanctum::actingAs($restrictedUser);

        $restrictedResponse = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/reports/materials');
        $restrictedResponse->assertStatus(200);
        $restrictedResponse->assertJsonCount(1);
        $restrictedResponse->assertJsonFragment([
            'category' => 'Safety',
            'count' => 1,
        ]);

        Sanctum::actingAs($generalUser);

        $filteredResponse = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/reports/materials?client_ids[]=' . $clientA->public_id . '&client_ids[]=' . $clientB->public_id);
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertJsonFragment([
            'category' => 'Safety',
            'count' => 1,
        ]);
        $filteredResponse->assertJsonFragment([
            'category' => 'Compliance',
            'count' => 1,
        ]);

        $singleClientResponse = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/reports/materials?client_id=' . $clientB->public_id);
        $singleClientResponse->assertStatus(200);
        $singleClientResponse->assertJsonCount(1);
        $singleClientResponse->assertJsonFragment([
            'category' => 'Compliance',
            'count' => 1,
        ]);
    }
}

