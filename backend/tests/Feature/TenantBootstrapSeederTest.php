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

        $this->assertDatabaseCount('task_statuses', 4);
        $first = TenantDefaults::TASK_STATUSES[0];
        $this->assertDatabaseHas('task_statuses', [
            'slug' => $first['slug'],
            'color' => $first['color'],
            'position' => 1,
        ]);
        $this->assertDatabaseHas('task_types', ['name' => 'General Task']);
    }
}
