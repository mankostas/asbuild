<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\Notifier;
use App\Support\ListQuery;
use App\Http\Resources\TaskCommentResource;
use App\Support\PublicIdResolver;
use Illuminate\Validation\ValidationException;

class TaskCommentController extends Controller
{
    use ListQuery;

    public function __construct(private PublicIdResolver $publicIdResolver)
    {
    }

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

        $data = $this->validatePayload($request);

        $fileIds = $this->resolveIdentifiers($data['files'] ?? [], File::class, 'files', __('The selected file is invalid.'));
        $mentionIds = $this->resolveIdentifiers($data['mentions'] ?? [], User::class, 'mentions', __('The selected mention is invalid.'));

        $mentions = User::whereIn('id', $mentionIds)->get();
        foreach ($mentions as $user) {
            if ($user->tenant_id !== $request->user()->tenant_id || Gate::forUser($user)->denies('tasks.view')) {
                return response()->json(['message' => 'invalid mentions'], 422);
            }
        }

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        if (! empty($fileIds)) {
            $comment->files()->attach($fileIds);
        }

        if ($mentions->isNotEmpty()) {
            $comment->mentions()->attach($mentions->pluck('id'));
            $mentions->each(function ($user) use ($task) {
                $task->watchers()->firstOrCreate(['user_id' => $user->id]);
                app(Notifier::class)->send(
                    $user,
                    'comment',
                    'You were mentioned in a task comment.',
                    '/tasks/' . $task->public_id
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

        $data = $this->validatePayload($request);

        $fileIds = null;
        if (array_key_exists('files', $data)) {
            $fileIds = $this->resolveIdentifiers($data['files'], File::class, 'files', __('The selected file is invalid.'));
        }

        $mentionIds = $this->resolveIdentifiers($data['mentions'] ?? [], User::class, 'mentions', __('The selected mention is invalid.'));
        $mentions = User::whereIn('id', $mentionIds)->get();
        foreach ($mentions as $user) {
            if ($user->tenant_id !== $request->user()->tenant_id || Gate::forUser($user)->denies('tasks.view')) {
                return response()->json(['message' => 'invalid mentions'], 422);
            }
        }

        $comment->body = $data['body'];
        $comment->save();

        if ($fileIds !== null) {
            $comment->files()->sync($fileIds);
        }

        $comment->mentions()->sync($mentions->pluck('id'));
        if ($mentions->isNotEmpty()) {
            $comment->loadMissing('task');
            $mentions->each(function ($user) use ($comment) {
                $comment->task->watchers()->firstOrCreate(['user_id' => $user->id]);
                app(Notifier::class)->send(
                    $user,
                    'comment',
                    'You were mentioned in a task comment.',
                    '/tasks/' . $comment->task->public_id
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

    protected function validatePayload(Request $request): array
    {
        $input = $request->all();

        foreach (['files', 'mentions'] as $key) {
            if (array_key_exists($key, $input) && is_array($input[$key])) {
                $input[$key] = array_values(array_map(
                    static fn ($value) => $value === null ? null : (string) $value,
                    $input[$key]
                ));
            }
        }

        return validator($input, [
            'body' => 'required|string',
            'files' => 'array',
            'files.*' => 'string',
            'mentions' => 'array',
            'mentions.*' => 'string',
        ])->validate();
    }

    /**
     * @param array<int, string|null> $identifiers
     * @return array<int, int>
     */
    protected function resolveIdentifiers(array $identifiers, string $modelClass, string $attribute, string $message): array
    {
        $resolved = [];

        foreach (array_values($identifiers) as $index => $identifier) {
            if ($identifier === null || $identifier === '') {
                throw ValidationException::withMessages([
                    "{$attribute}.{$index}" => $message,
                ]);
            }

            $id = $this->publicIdResolver->resolve($modelClass, $identifier);

            if ($id === null) {
                throw ValidationException::withMessages([
                    "{$attribute}.{$index}" => $message,
                ]);
            }

            $resolved[] = $id;
        }

        return $resolved;
    }
}
