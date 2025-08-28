<?php

namespace Tests\Feature;

use App\Models\AppointmentType;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $role = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'abilities' => ['*'],
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
        // index
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/appointments')
            ->assertStatus(200);

        // create appointment type
        $type = AppointmentType::create([
            'name' => 'Type A',
            'form_schema' => ['type' => 'object', 'properties' => ['note' => ['type' => 'string']], 'required' => ['note']],
            'fields_summary' => ['note' => 'string'],
            'statuses' => [
                'draft' => ['assigned'],
                'assigned' => ['in_progress'],
            ],
        ]);

        // store
        $payload = [
            'scheduled_at' => '2024-01-01T00:00:00Z',
            'sla_start_at' => '2024-01-01T08:00:00Z',
            'sla_end_at' => '2024-01-01T17:00:00Z',
            'kau_notes' => 'Notes',
            'appointment_type_id' => $type->id,
            'form_data' => ['note' => 'Sample'],
        ];
        $appointmentId = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/appointments', $payload)
            ->assertStatus(201)
            ->json('data.id');

        // show
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/appointments/{$appointmentId}")
            ->assertStatus(200);

        // update
        $update = [
            'kau_notes' => 'Updated',
            'form_data' => ['note' => 'Updated'],
        ];
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/appointments/{$appointmentId}", $update)
            ->assertStatus(200)
            ->assertJsonPath('data.kau_notes', 'Updated');

        // destroy
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->deleteJson("/api/appointments/{$appointmentId}")
            ->assertStatus(200);
    }
}
