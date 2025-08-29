<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskWatcherController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        $task->watchers()->firstOrCreate([
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'watched'], 201);
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        $task->watchers()->where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'unwatched']);
    }
}
