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

class AppointmentCommentRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Appointment $appointment;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $role = Role::create(['name' => 'ClientAdmin', 'tenant_id' => $tenant->id]);
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

        $type = AppointmentType::create([
            'name' => 'Type A',
            'form_schema' => ['type' => 'object', 'properties' => ['note' => ['type' => 'string']], 'required' => ['note']],
            'fields_summary' => ['note' => 'string'],
        ]);

        $appointment = Appointment::create([
            'tenant_id' => $tenant->id,
            'status' => Appointment::STATUS_DRAFT,
            'appointment_type_id' => $type->id,
            'form_data' => ['note' => 'Sample'],
        ]);

        $this->tenant = $tenant;
        $this->appointment = $appointment;
        $this->user = $user;
    }

    public function test_crud_routes_work(): void
    {
        $appointmentId = $this->appointment->id;

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/appointments/{$appointmentId}/comments")
            ->assertStatus(200);

        $commentId = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson("/api/appointments/{$appointmentId}/comments", ['body' => 'First comment'])
            ->assertStatus(201)
            ->json('id');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/comments/{$commentId}")
            ->assertStatus(200);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/comments/{$commentId}", ['body' => 'Updated comment'])
            ->assertStatus(200)
            ->assertJsonPath('body', 'Updated comment');

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->deleteJson("/api/comments/{$commentId}")
            ->assertStatus(200);
    }

    public function test_tenant_security_enforced(): void
    {
        $appointmentId = $this->appointment->id;

        $commentId = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson("/api/appointments/{$appointmentId}/comments", ['body' => 'Secure comment'])
            ->json('id');

        $tenantB = Tenant::create(['name' => 'Other Tenant']);
        $roleB = Role::create(['name' => 'ClientAdmin', 'tenant_id' => $tenantB->id]);
        $userB = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenantB->id,
            'phone' => '654321',
            'address' => 'Street 2',
        ]);
        $userB->roles()->attach($roleB->id, ['tenant_id' => $tenantB->id]);
        Sanctum::actingAs($userB);

        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/appointments/{$appointmentId}/comments")
            ->assertStatus(403);
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson("/api/appointments/{$appointmentId}/comments", ['body' => 'Bad comment'])
            ->assertStatus(403);
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/comments/{$commentId}")
            ->assertStatus(403);
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/comments/{$commentId}", ['body' => 'Hax'])
            ->assertStatus(403);
        $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->deleteJson("/api/comments/{$commentId}")
            ->assertStatus(403);
    }
}
