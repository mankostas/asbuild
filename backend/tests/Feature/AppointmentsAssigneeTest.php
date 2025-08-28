<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentsAssigneeTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignee_persisted_when_creating_appointment(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant']);
        $adminRole = Role::create(['name' => 'ClientAdmin', 'slug' => 'client_admin', 'tenant_id' => $tenant->id]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $employee = User::create([
            'name' => 'Employee',
            'email' => 'emp@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $admin->roles()->attach($adminRole->id, ['tenant_id' => $tenant->id]);

        $type = AppointmentType::create([
            'name' => 'General',
            'tenant_id' => $tenant->id,
            'form_schema' => [
                'type' => 'object',
                'properties' => [
                    'assignee_field' => ['kind' => 'assignee'],
                ],
            ],
        ]);

        Sanctum::actingAs($admin);

        $payload = [
            'appointment_type_id' => $type->id,
            'assignee' => ['kind' => 'employee', 'id' => $employee->id],
        ];

        $id = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/appointments', $payload)
            ->assertStatus(201)
            ->json('data.id');

        $appointment = Appointment::find($id);
        $this->assertEquals(User::class, $appointment->assignee_type);
        $this->assertEquals($employee->id, $appointment->assignee_id);
    }
}
