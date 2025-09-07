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

class TaskTypeVersionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_list_and_publish_versions_for_tenant(): void
    {
        $tenantA = Tenant::create(['name' => 'A', 'features' => ['tasks']]);
        $tenantB = Tenant::create(['name' => 'B', 'features' => ['tasks']]);

        $superRole = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'abilities' => [],
            'level' => 0,
        ]);

        $super = User::create([
            'name' => 'SA',
            'email' => 'sa@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantB->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $super->roles()->attach($superRole->id, ['tenant_id' => $tenantB->id]);

        $creator = User::create([
            'name' => 'C',
            'email' => 'c@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);

        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenantA->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
        ]);

        $version = $type->versions()->create([
            'semver' => '1.0.0',
            'schema_json' => [],
            'statuses' => [],
            'status_flow_json' => [],
            'created_by' => $creator->id,
        ]);

        Sanctum::actingAs($super);

        $list = $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->getJson('/api/task-type-versions?task_type_id=' . $type->id)
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $list);
        $this->assertEquals($version->id, $list[0]['id']);

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->postJson("/api/task-type-versions/{$version->id}/publish")
            ->assertOk();

        $this->assertNotNull($version->fresh()->published_at);
    }

    public function test_super_admin_can_unpublish_version(): void
    {
        $tenantA = Tenant::create(['name' => 'A', 'features' => ['tasks']]);
        $tenantB = Tenant::create(['name' => 'B', 'features' => ['tasks']]);

        $superRole = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'abilities' => [],
            'level' => 0,
        ]);

        $super = User::create([
            'name' => 'SA',
            'email' => 'sa@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantB->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $super->roles()->attach($superRole->id, ['tenant_id' => $tenantB->id]);

        $creator = User::create([
            'name' => 'C',
            'email' => 'c@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantA->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);

        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenantA->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
        ]);

        $version = $type->versions()->create([
            'semver' => '1.0.0',
            'schema_json' => [],
            'statuses' => [],
            'status_flow_json' => [],
            'created_by' => $creator->id,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($super);

        $this->withHeader('X-Tenant-ID', $tenantA->id)
            ->putJson("/api/task-type-versions/{$version->id}/unpublish")
            ->assertOk();
    }

    public function test_tenant_admin_can_unpublish_version(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);

        $role = Role::create([
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_type_versions.manage'],
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

        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
        ]);

        $version = $type->versions()->create([
            'semver' => '1.0.0',
            'schema_json' => [],
            'statuses' => [],
            'status_flow_json' => [],
            'created_by' => $user->id,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->putJson("/api/task-type-versions/{$version->id}/unpublish")
            ->assertOk();
    }

    public function test_user_without_manage_ability_cannot_unpublish_version(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);

        $role = Role::create([
            'name' => 'Viewer',
            'slug' => 'viewer',
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

        $type = TaskType::create([
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => ['sections' => []],
            'statuses' => ['draft' => []],
        ]);

        $version = $type->versions()->create([
            'semver' => '1.0.0',
            'schema_json' => [],
            'statuses' => [],
            'status_flow_json' => [],
            'created_by' => $user->id,
            'published_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->putJson("/api/task-type-versions/{$version->id}/unpublish")
            ->assertForbidden();
    }
}
