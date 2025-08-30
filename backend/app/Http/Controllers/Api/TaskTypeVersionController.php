<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskType;
use App\Models\TaskTypeVersion;
use Illuminate\Http\Request;

class TaskTypeVersionController extends Controller
{
    public function index(Request $request)
    {
        $query = TaskTypeVersion::query()->whereHas('taskType', function ($q) use ($request) {
            $q->where('tenant_id', $request->user()->tenant_id);
        });
        if ($id = $request->query('task_type_id')) {
            $query->where('task_type_id', $id);
        }
        return response()->json(['data' => $query->orderByDesc('id')->get()]);
    }

    public function store(TaskType $taskType, Request $request)
    {
        $user = $request->user();
        $count = $taskType->versions()->count();
        $semver = ($count + 1) . '.0.0';
        $version = $taskType->versions()->create([
            'semver' => $semver,
            'schema_json' => $taskType->schema_json,
            'statuses' => $taskType->statuses,
            'status_flow_json' => $taskType->status_flow_json,
            'created_by' => $user->id,
        ]);
        return response()->json(['data' => $version], 201);
    }

    public function publish(TaskTypeVersion $taskTypeVersion)
    {
        if (! $taskTypeVersion->published_at) {
            $taskTypeVersion->published_at = now();
            $taskTypeVersion->save();
            $taskTypeVersion->taskType->update(['current_version_id' => $taskTypeVersion->id]);
        }
        return response()->json(['data' => $taskTypeVersion]);
    }

    public function deprecate(TaskTypeVersion $taskTypeVersion)
    {
        if (! $taskTypeVersion->deprecated_at) {
            $taskTypeVersion->deprecated_at = now();
            $taskTypeVersion->save();
        }
        return response()->json(['data' => $taskTypeVersion]);
    }
}
