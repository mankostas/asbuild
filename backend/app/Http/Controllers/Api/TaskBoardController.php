<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskStatusResource;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Services\BoardPositionService;
use App\Services\StatusFlowService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskBoardController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'type_id' => ['sometimes', 'integer'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $typeId = $request->integer('type_id');

        $statuses = TaskStatus::where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderBy('position')
            ->get();

        $columns = $statuses->map(function (TaskStatus $status) use ($tenantId, $typeId, $request) {
            $limit = 50;
            $query = Task::where('tenant_id', $tenantId)
                ->where('status_slug', $status->slug);
            if ($typeId) {
                $query->where('task_type_id', $typeId);
            }
            $total = (clone $query)->count();
            $tasks = $query->orderBy('board_position')->limit($limit + 1)->get();

            $hasMore = $tasks->count() > $limit;
            $tasks = $tasks->take($limit);

            return [
                'status' => TaskStatusResource::make($status)->toArray($request),
                'tasks' => TaskResource::collection($tasks)->toArray($request),
                'meta' => [
                    'total' => $total,
                    'has_more' => $hasMore,
                ],
            ];
        })
            ->values()
            ->all();

        return response()->json(['data' => $columns]);
    }

    public function move(Request $request, BoardPositionService $positions, StatusFlowService $flow)
    {
        $data = $request->validate([
            'task_id' => ['required', 'integer', Rule::exists('tasks', 'id')],
            'status_slug' => ['required', 'string', Rule::exists('task_statuses', 'slug')],
            'index' => ['required', 'integer', 'min:0'],
        ]);

        $task = Task::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($data['task_id']);
        $status = TaskStatus::where('slug', $data['status_slug'])->firstOrFail();

        if (! $flow->canTransition($task->status_slug, $status->slug, $task->type)) {
            return response()->json(['message' => 'invalid_transition'], 422);
        }
        if ($reason = $flow->checkConstraints($task, $status->slug)) {
            return response()->json(['message' => $reason], 422);
        }

        $positions->move($task, $status->slug, $data['index']);

        return new TaskResource($task->fresh());
    }
}
