<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskStatusResource;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Services\BoardPositionService;
use App\Services\StatusFlowService;
use App\Services\TaskQueryFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskBoardController extends Controller
{
    protected function tenantId(Request $request): int
    {
        return (int) $request->attributes->get('tenant_id', $request->user()->tenant_id);
    }

    public function index(Request $request, TaskQueryFilters $filters)
    {
        $request->validate([
            'type_ids' => ['sometimes', 'array'],
            'type_ids.*' => ['integer'],
            'assignee_id' => ['sometimes', 'integer'],
            'priority' => ['sometimes', 'integer'],
            'q' => ['sometimes', 'string'],
            'sla' => ['sometimes', 'string'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date'],
            'due_from' => ['sometimes', 'date'],
            'due_to' => ['sometimes', 'date'],
            'mine' => ['sometimes', 'boolean'],
            'breached_only' => ['sometimes', 'boolean'],
            'due_today' => ['sometimes', 'boolean'],
            'has_photos' => ['sometimes', 'boolean'],
        ]);

        $tenantId = $this->tenantId($request);

        $types = TaskType::where('tenant_id', $tenantId)
            ->get();

        $slugs = $types->flatMap(function (TaskType $type) {
            $statuses = $type->statuses ?? [];
            if (array_is_list($statuses)) {
                return collect($statuses)->pluck('slug');
            }

            return collect($statuses)->keys();
        })
            ->unique()
            ->all();
        $prefixedSlugs = array_map(fn ($s) => TaskStatus::prefixSlug($s, $tenantId), $slugs);

        $statuses = TaskStatus::whereIn('slug', $prefixedSlugs)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderBy('position')
            ->orderBy('slug')
            ->get();

        $columns = $statuses->map(function (TaskStatus $status) use ($tenantId, $request, $filters) {
            $limit = 50;
            $query = Task::where('tenant_id', $tenantId)
                ->where('status_slug', $status->slug);

            $filters->apply($query, $request);

            $total = (clone $query)->count();
            $tasks = $query
                ->with(['type', 'assignee'])
                ->withCount(['comments', 'attachments', 'watchers', 'subtasks'])
                ->orderBy('board_position')
                ->limit($limit + 1)
                ->get();

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

    public function column(Request $request, TaskQueryFilters $filters)
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'type_ids' => ['sometimes', 'array'],
            'type_ids.*' => ['integer'],
            'assignee_id' => ['sometimes', 'integer'],
            'priority' => ['sometimes', 'integer'],
            'q' => ['sometimes', 'string'],
            'sla' => ['sometimes', 'string'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date'],
            'due_from' => ['sometimes', 'date'],
            'due_to' => ['sometimes', 'date'],
            'mine' => ['sometimes', 'boolean'],
            'breached_only' => ['sometimes', 'boolean'],
            'due_today' => ['sometimes', 'boolean'],
            'has_photos' => ['sometimes', 'boolean'],
        ]);

        $tenantId = $this->tenantId($request);
        $prefixed = TaskStatus::prefixSlug($data['status'], $tenantId);
        $limit = 50;
        $page = max(1, $request->integer('page', 1));
        $offset = ($page - 1) * $limit;

        $query = Task::where('tenant_id', $tenantId)
            ->where('status_slug', $prefixed);

        $filters->apply($query, $request);

        $total = (clone $query)->count();
        $tasks = $query
            ->with(['type', 'assignee'])
            ->withCount(['comments', 'attachments', 'watchers', 'subtasks'])
            ->orderBy('board_position')
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

        $hasMore = $tasks->count() > $limit;
        $tasks = $tasks->take($limit);

        return response()->json([
            'data' => TaskResource::collection($tasks)->toArray($request),
            'meta' => [
                'total' => $total,
                'has_more' => $hasMore,
            ],
        ]);
    }

    public function move(Request $request, BoardPositionService $positions, StatusFlowService $flow)
    {
        $data = $request->validate([
            'task_id' => ['required', 'integer', Rule::exists('tasks', 'id')],
            'status_slug' => ['required', 'string'],
            'index' => ['required', 'integer', 'min:0'],
        ]);

        $tenantId = $this->tenantId($request);
        $prefixed = TaskStatus::prefixSlug($data['status_slug'], $tenantId);
        $task = Task::where('tenant_id', $tenantId)
            ->findOrFail($data['task_id']);
        $status = TaskStatus::where('slug', $prefixed)->firstOrFail();

        if ($task->status_slug !== $status->slug) {
            $canManage = $request->user()->can('tasks.manage');

            if (! $canManage && $status->slug !== $task->previous_status_slug && ! $flow->canTransition(TaskStatus::stripPrefix($task->status_slug), TaskStatus::stripPrefix($status->slug), $task->type)) {
                return response()->json(['message' => 'invalid_transition'], 422);
            }

            if (TaskStatus::stripPrefix($status->slug) === 'assigned' && empty($task->assigned_user_id)) {
                $task->assigned_user_id = $request->user()->id;
            }

            if (! $canManage) {
                $flow->checkConstraints($task, TaskStatus::stripPrefix($status->slug));
            }
        }

        $positions->move($task, $status->slug, $data['index']);

        $task = $task->fresh(['type', 'assignee', 'watchers'])
            ->loadCount(['comments', 'attachments', 'watchers', 'subtasks']);

        return new TaskResource($task);
    }
}
