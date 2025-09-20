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
use App\Support\PublicIdGenerator;

class TaskListFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_filter_query_returns_expected(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.view', 'tasks.manage'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
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
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type', 'tenant_id' => $tenant->id
        ]);
        $status = TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'slug' => 'open', 'name' => 'Open', 'tenant_id' => $tenant->id
        ]);

        $matching = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => $status->slug,
            'assigned_user_id' => $user->id,
            'priority' => 1,
            'due_at' => '2025-01-10',
        ]);
        $file = File::create([
            'public_id' => PublicIdGenerator::generate(),
            'path' => 'a',
            'filename' => 'a',
            'mime_type' => 'image/png',
            'size' => 100,
        ]);
        $matching->attachments()->attach($file->id);

        Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'task_type_id' => $type->id,
            'status_slug' => \App\Models\TaskStatus::prefixSlug('closed', $tenant->id),
            'assigned_user_id' => $user->id,
            'priority' => 2,
            'due_at' => '2025-02-10',
        ]);

        $query = http_build_query([
            'type' => $type->id,
            'status' => 'open',
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
