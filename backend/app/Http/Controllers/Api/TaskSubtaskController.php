<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskSubtask;
use Illuminate\Http\Request;

class TaskSubtaskController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'title' => 'required|string',
            'is_completed' => 'boolean',
            'assigned_user_id' => 'nullable|integer',
            'is_required' => 'boolean',
        ]);
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
            'assigned_user_id' => 'nullable|integer',
            'is_required' => 'sometimes|boolean',
        ]);
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
            'order.*' => 'integer',
        ]);
        foreach ($data['order'] as $index => $id) {
            $task->subtasks()->where('id', $id)->update(['position' => $index + 1]);
        }
        return response()->json(['message' => 'reordered']);
    }
}
