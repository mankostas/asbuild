<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Support\PublicIdResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TaskSubtaskController extends Controller
{
    public function __construct(private PublicIdResolver $publicIdResolver)
    {
    }

    public function store(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $input = $request->all();

        if (array_key_exists('assigned_user_id', $input) && $input['assigned_user_id'] !== null) {
            $input['assigned_user_id'] = (string) $input['assigned_user_id'];
        }

        $data = validator($input, [
            'title' => 'required|string',
            'is_completed' => 'boolean',
            'assigned_user_id' => ['nullable', 'string'],
            'is_required' => 'boolean',
        ])->validate();
        if (! empty($data['assigned_user_id'])) {
            $resolved = $this->publicIdResolver->resolve(User::class, $data['assigned_user_id']);

            if ($resolved === null) {
                throw ValidationException::withMessages([
                    'assigned_user_id' => __('The selected assignee is invalid.'),
                ]);
            }

            $data['assigned_user_id'] = $resolved;
        }
        $data['position'] = ($task->subtasks()->max('position') ?? 0) + 1;
        $subtask = $task->subtasks()->create($data);
        return response()->json($subtask, 201);
    }

    public function update(Request $request, Task $task, TaskSubtask $subtask)
    {
        $this->authorize('update', $task);
        if ($subtask->task_id !== $task->id) {
            abort(404);
        }
        $input = $request->all();

        if (array_key_exists('assigned_user_id', $input) && $input['assigned_user_id'] !== null) {
            $input['assigned_user_id'] = (string) $input['assigned_user_id'];
        }

        $data = validator($input, [
            'title' => 'sometimes|required|string',
            'is_completed' => 'sometimes|boolean',
            'assigned_user_id' => ['nullable', 'string'],
            'is_required' => 'sometimes|boolean',
        ])->validate();
        if (array_key_exists('assigned_user_id', $data)) {
            if ($data['assigned_user_id']) {
                $resolved = $this->publicIdResolver->resolve(User::class, $data['assigned_user_id']);

                if ($resolved === null) {
                    throw ValidationException::withMessages([
                        'assigned_user_id' => __('The selected assignee is invalid.'),
                    ]);
                }

                $data['assigned_user_id'] = $resolved;
            } else {
                $data['assigned_user_id'] = null;
            }
        }
        $subtask->update($data);
        return response()->json($subtask);
    }

    public function destroy(Task $task, TaskSubtask $subtask)
    {
        $this->authorize('update', $task);
        if ($subtask->task_id !== $task->id) {
            abort(404);
        }
        $subtask->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function reorder(Request $request, Task $task)
    {
        $this->authorize('update', $task);
        $input = $request->all();

        if (isset($input['order']) && is_array($input['order'])) {
            $input['order'] = array_values(array_map(
                static fn ($value) => $value === null ? null : (string) $value,
                $input['order']
            ));
        }

        $data = validator($input, [
            'order' => 'required|array',
            'order.*' => ['required', 'string'],
        ])->validate();
        foreach ($data['order'] as $index => $identifier) {
            $subtaskId = $this->publicIdResolver->resolve(TaskSubtask::class, $identifier);

            if ($subtaskId === null) {
                throw ValidationException::withMessages([
                    "order.$index" => __('The selected subtask is invalid.'),
                ]);
            }

            $task->subtasks()
                ->where('id', $subtaskId)
                ->update(['position' => $index + 1]);
        }
        return response()->json(['message' => 'reordered']);
    }
}
