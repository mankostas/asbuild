<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskSlaPolicyRequest;
use App\Models\TaskSlaPolicy;
use App\Models\TaskType;
use Illuminate\Http\Request;

class TaskSlaPolicyController extends Controller
{
    public function index(TaskType $taskType, Request $request)
    {
        if ($request->user()->cannot('task_sla_policies.manage')) {
            abort(403);
        }
        return response()->json(['data' => $taskType->slaPolicies()->get()]);
    }

    public function store(TaskSlaPolicyRequest $request, TaskType $taskType)
    {
        if ($request->user()->cannot('task_sla_policies.manage')) {
            abort(403);
        }
        $data = $request->validated();
        $policy = $taskType->slaPolicies()->create($data);
        return response()->json(['data' => $policy], 201);
    }

    public function update(TaskSlaPolicyRequest $request, TaskType $taskType, TaskSlaPolicy $taskSlaPolicy)
    {
        if ($request->user()->cannot('task_sla_policies.manage')) {
            abort(403);
        }
        if ($taskSlaPolicy->task_type_id !== $taskType->id) {
            abort(404);
        }
        $taskSlaPolicy->update($request->validated());
        return response()->json(['data' => $taskSlaPolicy]);
    }

    public function destroy(TaskType $taskType, TaskSlaPolicy $taskSlaPolicy, Request $request)
    {
        if ($request->user()->cannot('task_sla_policies.manage')) {
            abort(403);
        }
        if ($taskSlaPolicy->task_type_id !== $taskType->id) {
            abort(404);
        }
        $taskSlaPolicy->delete();
        return response()->json(['message' => 'deleted']);
    }
}
