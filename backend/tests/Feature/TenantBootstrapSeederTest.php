<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TenantBootstrapSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_populates_defaults(): void
    {
        Artisan::call('db:seed', ['--class' => \Database\Seeders\TenantBootstrapSeeder::class]);

        $this->assertDatabaseCount('task_statuses', 4);
        $this->assertDatabaseHas('task_statuses', [
            'slug' => 'todo',
            'color' => '#9ca3af',
            'position' => 1,
        ]);
        $this->assertDatabaseHas('task_types', ['name' => 'General Task']);
    }
}
