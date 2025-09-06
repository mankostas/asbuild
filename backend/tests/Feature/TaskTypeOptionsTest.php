<?php
namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TaskType;
use App\Models\TaskTypeVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTypeOptionsTest extends TestCase
{
    use RefreshDatabase;

    private function seedType(Tenant $tenant, User $user): void
    {
        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'current_version_id' => null,
        ]);
        $version = TaskTypeVersion::create([
            'task_type_id' => $type->id,
            'semver' => '1.0.0',
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
            'status_flow_json' => [],
            'created_by' => $user->id,
            'published_at' => now(),
        ]);
        $type->current_version_id = $version->id;
        $type->save();
    }

    public function test_tasks_create_user_can_fetch_options(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks', 'task_types']]);
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.create'],
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

        $this->seedType($tenant, $user);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/task-types/options')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_missing_ability_cannot_fetch_options(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks', 'task_types']]);
        $role = Role::create([
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => [],
            'level' => 1,
        ]);
        $user = User::create([
            'name' => 'U2',
            'email' => 'u2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/task-types/options')
            ->assertStatus(403);
    }
}
