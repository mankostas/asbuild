<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentTypeRoutesTest extends TestCase
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
            'abilities' => ['types.manage'],
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
        $this->tenant = $tenant; // store for headers
    }

    public function test_crud_routes_work(): void
    {
        // index
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/appointment-types')
            ->assertStatus(200);

        // store
        $payload = [
            'name' => 'Type A',
            'form_schema' => json_encode(['type' => 'object', 'properties' => ['note' => ['type' => 'string']], 'required' => ['note']]),
            'fields_summary' => json_encode(['note' => 'string']),
            'statuses' => json_encode([
                'draft' => ['assigned'],
                'assigned' => ['in_progress'],
            ]),
        ];
        $typeId = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/appointment-types', $payload)
            ->assertStatus(201)
            ->json('data.id');

        // show
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/appointment-types/{$typeId}")
            ->assertStatus(200);

        // update without name
        $update = [
            'name' => 'Type A',
            'fields_summary' => json_encode(['note' => 'text']),
            'statuses' => json_encode([
                'draft' => ['assigned'],
                'assigned' => ['in_progress'],
            ]),
        ];
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/appointment-types/{$typeId}", $update)
            ->assertStatus(200)
            ->assertJsonPath('data.fields_summary.note', 'text');

        // destroy
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->deleteJson("/api/appointment-types/{$typeId}")
            ->assertStatus(200);
    }
}
