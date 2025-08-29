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
use App\Http\Resources\TaskCommentResource;

class TaskCommentController extends Controller
{
    use ListQuery;

    public function index(Request $request, Task $task)
    {
        $this->authorize('view', $task);
        $base = $task->comments()->with(['user', 'files', 'mentions']);
        $result = $this->listQuery($base, $request, ['body'], ['created_at']);
        return TaskCommentResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'body' => 'required|string',
            'files' => 'array',
            'files.*' => 'integer|exists:files,id',
            'mentions' => 'array',
            'mentions.*' => 'integer|exists:users,id',
        ]);

        $mentions = User::whereIn('id', $data['mentions'] ?? [])->get();
        foreach ($mentions as $user) {
            if ($user->tenant_id !== $request->user()->tenant_id || Gate::forUser($user)->denies('tasks.view')) {
                return response()->json(['message' => 'invalid mentions'], 422);
            }
        }

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        if (!empty($data['files'])) {
            $comment->files()->attach($data['files']);
        }

        if ($mentions->isNotEmpty()) {
            $comment->mentions()->attach($mentions->pluck('id'));
            $mentions->each(function ($user) use ($task) {
                app(Notifier::class)->send(
                    $user,
                    'comment',
                    'You were mentioned in a task comment.',
                    '/tasks/' . $task->id
                );
            });
        }

        return (new TaskCommentResource($comment->load(['user', 'files', 'mentions'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(TaskComment $comment)
    {
        $this->authorize('view', $comment->task);
        return new TaskCommentResource($comment->load(['user', 'files', 'mentions']));
    }

    public function update(Request $request, TaskComment $comment)
    {
        $this->authorize('update', $comment->task);

        $data = $request->validate([
            'body' => 'required|string',
            'files' => 'array',
            'files.*' => 'integer|exists:files,id',
            'mentions' => 'array',
            'mentions.*' => 'integer|exists:users,id',
        ]);

        $mentions = User::whereIn('id', $data['mentions'] ?? [])->get();
        foreach ($mentions as $user) {
            if ($user->tenant_id !== $request->user()->tenant_id || Gate::forUser($user)->denies('tasks.view')) {
                return response()->json(['message' => 'invalid mentions'], 422);
            }
        }

        $comment->body = $data['body'];
        $comment->save();

        if (array_key_exists('files', $data)) {
            $comment->files()->sync($data['files']);
        }

        $comment->mentions()->sync($mentions->pluck('id'));
        if ($mentions->isNotEmpty()) {
            $mentions->each(function ($user) use ($comment) {
                app(Notifier::class)->send(
                    $user,
                    'comment',
                    'You were mentioned in a task comment.',
                    '/tasks/' . $comment->task_id
                );
            });
        }

        return new TaskCommentResource($comment->load(['user', 'files', 'mentions']));
    }

    public function destroy(TaskComment $comment)
    {
        $this->authorize('delete', $comment->task);
        $comment->delete();
        return response()->json(['message' => 'deleted']);
    }
}
