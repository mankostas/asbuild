<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskAutomationRequest;
use App\Models\TaskAutomation;
use App\Models\TaskType;

class TaskAutomationController extends Controller
{
    public function index(TaskType $taskType)
    {
        return response()->json(['data' => $taskType->automations()->get()]);
    }

    public function store(TaskAutomationRequest $request, TaskType $taskType)
    {
        $automation = $taskType->automations()->create($request->validated());
        return response()->json(['data' => $automation], 201);
    }

    public function update(TaskAutomationRequest $request, TaskType $taskType, TaskAutomation $taskAutomation)
    {
        if ($taskAutomation->task_type_id !== $taskType->id) {
            abort(404);
        }
        $taskAutomation->update($request->validated());
        return response()->json(['data' => $taskAutomation]);
    }

    public function destroy(TaskType $taskType, TaskAutomation $taskAutomation)
    {
        if ($taskAutomation->task_type_id !== $taskType->id) {
            abort(404);
        }
        $taskAutomation->delete();
        return response()->json(['message' => 'deleted']);
    }
}
