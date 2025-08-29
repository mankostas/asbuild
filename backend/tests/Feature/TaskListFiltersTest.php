<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskListFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_filter_query_returns_expected(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.view', 'tasks.manage'],
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
        $status = TaskStatus::create(['slug' => 'open', 'name' => 'Open', 'tenant_id' => $tenant->id]);

        $matching = Task::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => $status->slug,
            'assignee_type' => User::class,
            'assignee_id' => $user->id,
            'priority' => 1,
            'due_at' => '2025-01-10',
        ]);
        $file = File::create([
            'path' => 'a',
            'filename' => 'a',
            'mime_type' => 'image/png',
            'size' => 100,
        ]);
        $matching->attachments()->attach($file->id);

        Task::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => 'closed',
            'assignee_type' => User::class,
            'assignee_id' => $user->id,
            'priority' => 2,
            'due_at' => '2025-02-10',
        ]);

        $query = http_build_query([
            'type' => $type->id,
            'status' => $status->slug,
            'assignee' => $user->id,
            'priority' => 1,
            'due_from' => '2025-01-01',
            'due_to' => '2025-01-15',
            'has_photos' => 1,
            'mine' => 1,
        ]);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/tasks?' . $query)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $matching->id]);

        \App\Models\Tenant::setCurrent(null);
        config()->set('tenant', []);
    }
}
