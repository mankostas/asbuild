<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use App\Support\TenantDefaults;
use Tests\TestCase;

class TenantBootstrapSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_populates_defaults(): void
    {
        Artisan::call('db:seed', ['--class' => \Database\Seeders\TenantBootstrapSeeder::class]);

        $this->assertDatabaseCount('task_statuses', 8);
        $first = TenantDefaults::TASK_STATUSES[0];
        $this->assertDatabaseHas('task_statuses', [
            'slug' => $first['slug'],
            'color' => $first['color'],
            'position' => 1,
        ]);
        $this->assertDatabaseHas('task_types', ['name' => 'General Task']);

        $flow = \DB::table('task_types')
            ->where('name', 'General Task')
            ->value('status_flow_json');
        $edges = json_decode((string) $flow, true);

        $this->assertTrue(in_array(['blocked', 'assigned'], $edges, true));
        $this->assertTrue(in_array(['review', 'completed'], $edges, true));
        $this->assertTrue(in_array(['review', 'redo'], $edges, true));
        $this->assertTrue(in_array(['review', 'rejected'], $edges, true));
        $this->assertTrue(in_array(['redo', 'in_progress'], $edges, true));

        $this->assertDatabaseCount('clients', 3);
        $this->assertDatabaseHas('clients', [
            'tenant_id' => 1,
            'name' => 'Bella Barker',
            'email' => 'bella.barker@example.test',
            'status' => 'active',
            'archived_at' => null,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('clients', [
            'tenant_id' => 1,
            'name' => 'Charlie Cat',
            'phone' => '555-200-0002',
            'status' => 'active',
            'archived_at' => null,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('clients', [
            'tenant_id' => 1,
            'name' => 'Oscar Otter',
            'email' => 'oscar.otter@example.test',
            'status' => 'active',
            'archived_at' => null,
            'deleted_at' => null,
        ]);
    }
}
