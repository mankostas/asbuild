<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TaskType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTypeVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_version_sets_current_and_tasks_use_it(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_types.manage', 'task_type_versions.manage', 'tasks.create', 'tasks.manage'],
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

        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
        ]);

        $version = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/task-types/{$type->id}/versions")
            ->assertCreated()
            ->json('data');

        $this->postJson("/api/task-type-versions/{$version['id']}/publish")
            ->assertOk();

        $type->refresh();
        $this->assertEquals($version['id'], $type->current_version_id);

        $task = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/tasks', [
                'tenant_id' => $tenant->id,
                'task_type_id' => $type->id,
                'form_data' => [],
            ])->assertCreated()->json('data');

        $this->assertEquals($version['id'], $task['task_type_version_id']);
    }
}
