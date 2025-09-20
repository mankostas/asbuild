<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TaskCommentAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_with_attachment_saves_file_relation(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'A',
            'email' => 'a@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);
        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'title' => 'T1',
        ]);
        $file = File::create([
            'public_id' => PublicIdGenerator::generate(),
            'path' => 'f.jpg',
            'filename' => 'f.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 100,
        ]);

        Sanctum::actingAs($user);
        app()->instance('tenant_id', $tenant->id);

        $res = $this->withHeader('X-Tenant-ID', $tenant->public_id)
            ->postJson("/api/tasks/{$task->public_id}/comments", [
                'body' => 'Hi',
                'files' => [$file->public_id],
            ]);

        $res->assertCreated();
        $commentPublicId = $res->json('data.id');
        $this->assertIsString($commentPublicId);

        $commentId = $this->idFromPublicId(TaskComment::class, $commentPublicId);
        $comment = TaskComment::query()->find($commentId);
        $this->assertNotNull($comment);
        $this->assertSame($commentId, $comment->getKey());
        $this->assertSame($commentPublicId, $comment->public_id);

        $this->assertDatabaseHas('task_comment_files', [
            'task_comment_id' => $commentId,
            'file_id' => $file->id,
        ]);
        app()->forgetInstance('tenant_id');
    }
}
