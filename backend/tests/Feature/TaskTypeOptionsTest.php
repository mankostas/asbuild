<?php
namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TaskType;
use App\Http\Middleware\Ability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TaskTypeOptionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->router->aliasMiddleware('ability', Ability::class);
    }

    private function seedType(Tenant $tenant, User $user): void
    {
        TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => [['slug' => 'draft']],
            'status_flow_json' => [],
        ]);
    }

    public function test_tasks_create_user_can_fetch_options(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks', 'task_types']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.create'],
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

        $this->seedType($tenant, $user);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/task-types/options')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_tasks_create_user_can_create_task(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks', 'task_types']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.create'],
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

        $this->seedType($tenant, $user);
        $type = TaskType::first();

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/tasks', ['task_type_id' => $type->id])
            ->assertCreated()
            ->assertJsonPath('data.task_type_id', $type->id);
    }

    public function test_missing_ability_cannot_fetch_options(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks', 'task_types']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => [],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
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
