<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\Notifier;
use App\Support\ListQuery;

class TaskCommentController extends Controller
{
    use ListQuery;

    public function index(Request $request, Task $task)
    {
        $this->authorize('view', $task);
        $base = $task->comments()->with(['user', 'files', 'mentions']);
        $result = $this->listQuery($base, $request, ['body'], ['created_at']);
        return response()->json($result);
    }

    public function store(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'body' => 'required|string',
            'files' => 'array',
            'files.*' => 'integer|exists:files,id',
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        if (!empty($data['files'])) {
            $comment->files()->attach($data['files']);
        }

        preg_match_all('/@([\w]+)/', $data['body'], $matches);
        $names = array_unique($matches[1] ?? []);
        if ($names) {
            $mentioned = User::whereIn('name', $names)->get();
            $allowed = $mentioned->filter(fn ($user) => Gate::forUser($user)->allows('view', $task));
            if ($allowed->isNotEmpty()) {
                $comment->mentions()->attach($allowed->pluck('id'));
                $allowed->each(function ($user) use ($task) {
                    app(Notifier::class)->send(
                        $user,
                        'comment',
                        'You were mentioned in a task comment.',
                        '/tasks/' . $task->id
                    );
                });
            }
        }

        return response()->json($comment->load(['user', 'files', 'mentions']), 201);
    }

    public function show(TaskComment $comment)
    {
        $this->authorize('view', $comment->task);
        return response()->json($comment->load(['user', 'files', 'mentions']));
    }

    public function update(Request $request, TaskComment $comment)
    {
        $this->authorize('update', $comment->task);

        $data = $request->validate([
            'body' => 'required|string',
            'files' => 'array',
            'files.*' => 'integer|exists:files,id',
        ]);

        $comment->body = $data['body'];
        $comment->save();

        if (array_key_exists('files', $data)) {
            $comment->files()->sync($data['files']);
        }

        preg_match_all('/@([\w]+)/', $data['body'], $matches);
        $names = array_unique($matches[1] ?? []);
        $comment->mentions()->sync([]);
        if ($names) {
            $mentioned = User::whereIn('name', $names)->get();
            $allowed = $mentioned->filter(fn ($user) => Gate::forUser($user)->allows('view', $comment->task));
            if ($allowed->isNotEmpty()) {
                $comment->mentions()->sync($allowed->pluck('id'));
                $allowed->each(function ($user) use ($comment) {
                    app(Notifier::class)->send(
                        $user,
                        'comment',
                        'You were mentioned in a task comment.',
                        '/tasks/' . $comment->task_id
                    );
                });
            }
        }

        return response()->json($comment->load(['user', 'files', 'mentions']));
    }

    public function destroy(TaskComment $comment)
    {
        $this->authorize('delete', $comment->task);
        $comment->delete();
        return response()->json(['message' => 'deleted']);
    }
}
