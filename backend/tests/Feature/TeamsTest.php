<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_membership_sync(): void
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
        $user1 = User::create([
            'name' => 'User1',
            'email' => 'user1@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user2 = User::create([
            'name' => 'User2',
            'email' => 'user2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $admin->roles()->attach($adminRole->id, ['tenant_id' => $tenant->id]);
        $team = Team::create(['tenant_id' => $tenant->id, 'name' => 'Alpha', 'description' => '']);
        Sanctum::actingAs($admin);

        \DB::table('team_employee')->insert([
            ['team_id' => $team->id, 'employee_id' => $user1->id],
            ['team_id' => $team->id, 'employee_id' => $user2->id],
        ]);

        $members = $team->belongsToMany(User::class, 'team_employee', 'team_id', 'employee_id')->get();
        $this->assertCount(2, $members);
    }
}
