<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskSubtask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskSubtaskController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'title' => 'required|string',
            'is_completed' => 'boolean',
            'assigned_user_id' => ['nullable', 'string', 'ulid', Rule::exists('users', 'public_id')],
            'is_required' => 'boolean',
        ]);
        if (! empty($data['assigned_user_id'])) {
            $data['assigned_user_id'] = User::where('public_id', $data['assigned_user_id'])->value('id');
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
        $data = $request->validate([
            'title' => 'sometimes|required|string',
            'is_completed' => 'sometimes|boolean',
            'assigned_user_id' => ['nullable', 'string', 'ulid', Rule::exists('users', 'public_id')],
            'is_required' => 'sometimes|boolean',
        ]);
        if (array_key_exists('assigned_user_id', $data)) {
            $data['assigned_user_id'] = $data['assigned_user_id']
                ? User::where('public_id', $data['assigned_user_id'])->value('id')
                : null;
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
        $data = $request->validate([
            'order' => 'required|array',
            'order.*' => ['string', 'ulid', Rule::exists('task_subtasks', 'public_id')],
        ]);
        foreach ($data['order'] as $index => $id) {
            $task->subtasks()
                ->where('public_id', $id)
                ->update(['position' => $index + 1]);
        }
        return response()->json(['message' => 'reordered']);
    }
}
