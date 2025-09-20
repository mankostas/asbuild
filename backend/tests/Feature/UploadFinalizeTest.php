<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class UploadFinalizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalize_stores_file_and_binds_task_metadata(): void
    {
        Storage::fake('local');
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'User',
            'slug' => 'user',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.attach.upload'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id, 'user_id' => $user->id
        ]);

        $file = UploadedFile::fake()->image('final.jpg', 10, 10)->size(10);
        Storage::put('files/final.jpg', file_get_contents($file->getRealPath()));

        $response = $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/uploads/xyz/finalize', [
                'filename' => 'final.jpg',
                'task_id' => $task->public_id,
                'field_key' => 'photo',
                'section_key' => 'sec1',
            ])
            ->assertOk()
            ->assertJsonStructure(['file_id', 'name']);

        $storedFile = File::first();

        $this->assertNotNull($storedFile);
        $this->assertSame($storedFile->public_id, $response->json('file_id'));

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $task->id,
            'field_key' => 'photo',
            'section_key' => 'sec1',
        ]);
    }
}
