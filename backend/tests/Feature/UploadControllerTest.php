<?php

namespace Tests\Feature;

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

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalize_attaches_file_to_task_with_field_and_section(): void
    {
        Storage::fake('local');
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create(['name' => 'User', 'slug' => 'user', 'tenant_id' => $tenant->id, 'abilities' => ['tasks.attach.upload'], 'level' => 1]);
        $user = User::create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        $task = Task::create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100)->size(100);
        Storage::put('files/test.jpg', file_get_contents($file->getRealPath()));

        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson('/api/uploads/abc/finalize', [
                'filename' => 'test.jpg',
                'task_id' => $task->id,
                'field_key' => 'photo',
                'section_key' => 'sec1',
            ])
            ->assertStatus(200)
            ->assertJsonStructure(['file_id', 'name']);

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $task->id,
            'field_key' => 'photo',
            'section_key' => 'sec1',
        ]);
    }

    public function test_chunk_validates_mime_and_size(): void
    {
        Storage::fake('local');
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create(['name' => 'User', 'slug' => 'user', 'tenant_id' => $tenant->id, 'abilities' => ['tasks.attach.upload'], 'level' => 1]);
        $user = User::create([
            'name' => 'U',
            'email' => 'u2@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $badMime = UploadedFile::fake()->create('bad.txt', 10, 'text/plain');
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->post('/api/uploads/chunk', [
                'upload_id' => '1',
                'index' => 0,
                'total' => 1,
                'filename' => 'bad.txt',
                'chunk' => $badMime,
            ])
            ->assertStatus(422);

        $big = UploadedFile::fake()->create('big.jpg', config('security.max_upload_size') + 1000);
        $this->withHeader('X-Tenant-ID', $tenant->id)
            ->post('/api/uploads/chunk', [
                'upload_id' => '2',
                'index' => 0,
                'total' => 1,
                'filename' => 'big.jpg',
                'chunk' => $big,
            ])
            ->assertStatus(422);
    }
}
