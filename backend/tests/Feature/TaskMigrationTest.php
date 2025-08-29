<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaskMigrationTest extends TestCase
{
    public function test_migrations_run_and_rollback(): void
    {
        Artisan::call('migrate:fresh');
        $this->assertTrue(Schema::hasTable('tasks'));
        Artisan::call('migrate:rollback');
        $this->assertFalse(Schema::hasTable('tasks'));
    }
}
