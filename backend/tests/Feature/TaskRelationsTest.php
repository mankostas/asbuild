<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskSubtask;
use App\Models\TaskWatcher;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TaskRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_relations_work(): void
    {
        $tenant = Tenant::create(['name' => 'T']);
        $user = User::create([
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);

        $task = Task::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'title' => 'Task',
            'assigned_user_id' => $user->id,
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'body' => 'Note',
        ]);

        $watcher = TaskWatcher::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
        ]);

        $subtask = TaskSubtask::create([
            'task_id' => $task->id,
            'title' => 'Sub',
        ]);

        $this->assertTrue($task->comments->contains($comment));
        $this->assertTrue($task->watchers->contains($watcher));
        $this->assertTrue($task->subtasks->contains($subtask));
        $this->assertInstanceOf(Task::class, $comment->task);
        $this->assertInstanceOf(User::class, $task->assignee);
    }
}
