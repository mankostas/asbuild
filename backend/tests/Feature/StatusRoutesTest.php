<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StatusRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $role = Role::create([
            'name' => 'ClientAdmin',
            'tenant_id' => $tenant->id,
            'abilities' => ['statuses.manage'],
        ]);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);
        $this->tenant = $tenant;
    }

    public function test_crud_routes_work(): void
    {
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/statuses')
            ->assertStatus(200);

        $payload = ['name' => 'Open'];
        $statusId = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/statuses', $payload)
            ->assertStatus(201)
            ->json('data.id');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/statuses/{$statusId}")
            ->assertStatus(200);

        $update = ['name' => 'Closed'];
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/statuses/{$statusId}", $update)
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Closed');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->deleteJson("/api/statuses/{$statusId}")
            ->assertStatus(200);
    }
}

