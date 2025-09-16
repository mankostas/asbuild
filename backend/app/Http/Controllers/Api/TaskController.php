<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\TaskStatus;
use App\Services\FormSchemaService;
use App\Services\StatusFlowService;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\TaskUpsertRequest;
use App\Support\ListQuery;

class TaskController extends Controller
{
    use ListQuery;

    public function __construct(
        private FormSchemaService $formSchemaService,
        private StatusFlowService $statusFlow
    ) {
    }

    public function index(Request $request)
    {
        $base = Task::where('tenant_id', $request->user()->tenant_id)
            ->with(['type', 'assignee', 'status'])
            ->withCount(['comments', 'attachments', 'watchers', 'subtasks']);

        if ($type = $request->query('type')) {
            $base->where('task_type_id', $type);
        }

        if ($status = $request->query('status')) {
            $base->where('status_slug', TaskStatus::prefixSlug($status, $request->user()->tenant_id));
        }

        if ($assignee = $request->query('assignee')) {
            $base->where('assigned_user_id', $assignee);
        }

        if ($priority = $request->query('priority')) {
            $base->where('priority', $priority);
        }

        if ($dueFrom = $request->query('due_from')) {
            $base->whereDate('due_at', '>=', $dueFrom);
        }

        if ($dueTo = $request->query('due_to')) {
            $base->whereDate('due_at', '<=', $dueTo);
        }

        if ($request->boolean('has_photos')) {
            $base->whereHas('attachments');
        }

        if ($request->boolean('mine')) {
            $base->where('assigned_user_id', $request->user()->id);
        }

        $result = $this->listQuery($base, $request, [], ['created_at', 'due_at', 'priority', 'board_position']);

        return TaskResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(TaskUpsertRequest $request)
    {
        $this->authorize('create', Task::class);

        $data = $request->validated();
        $canOverride = auth()->user()->can('tasks.sla.override');
        if (! $canOverride) {
            unset($data['sla_start_at'], $data['sla_end_at']);
        }

        $tenantId = $request->user()->tenant_id;
        if ($request->user()->isSuperAdmin()) {
            $tenantId = $request->attributes->get('tenant_id', $tenantId);
        }
        $data['tenant_id'] = $tenantId;

        $type = null;
        if (isset($data['task_type_id'])) {
            $type = TaskType::find($data['task_type_id']);
        }
        $statuses = $type?->statuses ?? [];
        $data['status'] = $statuses ? array_key_first($statuses) : Task::STATUS_DRAFT;
        $data['status_slug'] = TaskStatus::prefixSlug($data['status'], $tenantId);
        $this->validateAgainstSchema($type, $data['form_data'] ?? [], null);
        $this->formSchemaService->mapAssignee($type->schema_json ?? [], $data);
        $this->formSchemaService->mapReviewer($type->schema_json ?? [], $data);
        if (isset($data['form_data'])) {
            $this->formSchemaService->sanitizeRichText($type->schema_json ?? [], $data['form_data']);
        }

        $task = new Task();
        $task->fill($data);

        if ($task->assigned_user_id !== null) {
            $this->authorize('assign', $task);
        }

        if (! $canOverride || ! $task->sla_end_at) {
            app(\App\Services\TaskSlaService::class)->apply($task);
        }
        $task->save();
        $task->watchers()->firstOrCreate(['user_id' => $request->user()->id]);
        if ($task->assigned_user_id) {
            $task->watchers()->firstOrCreate(['user_id' => $task->assigned_user_id]);
        }
        $task->load('type', 'assignee', 'watchers');

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);
        return new TaskResource($task->load('comments', 'type', 'assignee', 'watchers'));
    }

    public function update(TaskUpsertRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validated();
        $canOverride = auth()->user()->can('tasks.sla.override');
        if (! $canOverride) {
            unset($data['sla_start_at'], $data['sla_end_at']);
        }

        unset($data['status']);

        $typeId = $data['task_type_id'] ?? $task->task_type_id;
        $type = $typeId ? TaskType::find($typeId) : $task->type;
        $this->validateAgainstSchema($type, $data['form_data'] ?? $task->form_data ?? [], $task);
        $this->formSchemaService->mapAssignee($type->schema_json ?? [], $data);
        $this->formSchemaService->mapReviewer($type->schema_json ?? [], $data);
        if (isset($data['form_data'])) {
            $this->formSchemaService->sanitizeRichText($type->schema_json ?? [], $data['form_data']);
        }
        $task->fill($data);

        if ($task->isDirty('assigned_user_id')) {
            $this->authorize('assign', $task);
        }
        if (! $canOverride || ! $task->sla_end_at) {
            app(\App\Services\TaskSlaService::class)->apply($task);
        }
        $task->save();
        if ($task->assigned_user_id) {
            $task->watchers()->firstOrCreate(['user_id' => $task->assigned_user_id]);
        }

        return new TaskResource($task->load('comments', 'type', 'assignee', 'watchers'));
    }

    public function assign(Request $request, Task $task)
    {
        $this->authorize('assign', $task);

        $data = $request->validate([
            'assigned_user_id' => ['nullable', 'integer'],
        ]);

        $task->assigned_user_id = $data['assigned_user_id'] ?? null;
        $task->save();

        if ($task->assigned_user_id) {
            $task->watchers()->firstOrCreate(['user_id' => $task->assigned_user_id]);
        }

        return new TaskResource($task->load('assignee', 'watchers'));
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validate(['status' => 'required|string']);
        $next = $data['status'];
        $prefixed = TaskStatus::prefixSlug($next, $task->tenant_id);
        $type = $task->type;

        if ($task->status !== $next) {
            $canManage = $request->user()->can('tasks.manage');
            if (! $canManage && $prefixed !== $task->previous_status_slug && ! $this->statusFlow->canTransition($task->status, $next, $type)) {
                return response()->json(['message' => 'invalid_transition'], 422);
            }
            if (! $canManage) {
                $this->statusFlow->checkConstraints($task, $next);
            }

            $prev = $task->status;
            $task->status = $next;
            $task->status_slug = $prefixed;
            $task->previous_status_slug = TaskStatus::prefixSlug($prev, $task->tenant_id);
            if ($next === Task::STATUS_IN_PROGRESS && ! $task->started_at) {
                $task->started_at = now();
            }
            if ($next === Task::STATUS_COMPLETED && ! $task->completed_at) {
                $task->completed_at = now();
            }
            $task->save();
        }

        return new TaskResource($task->load('type', 'assignee'));
    }

    protected function validateAgainstSchema($type, array $data, ?Task $task = null): void
    {
        if (! $type || ! $type->schema_json) {
            return;
        }

        $this->formSchemaService->assertCanEdit($type->schema_json, $data, auth()->user());

        $fields = collect($type->schema_json['sections'] ?? [])
            ->flatMap(fn ($s) => $s['fields'] ?? []);
        $logic = $this->formSchemaService->evaluateLogic($type->schema_json, $data);
        $visible = collect($logic['visible']);
        $showTargets = collect($logic['showTargets']);
        $requiredOverride = collect($logic['required']);

        foreach ($fields as $field) {
            $key = $field['key'] ?? null;
            if (! $key) {
                continue;
            }
            $rules = $field['validations'] ?? [];

            $hasShow = $showTargets->contains($key);
            $isVisible = ! $hasShow || $visible->contains($key);
            if (! $isVisible) {
                continue;
            }

            $isRequired = ($rules['required'] ?? false) || $requiredOverride->contains($key);
            if ($isRequired && ! array_key_exists($key, $data)) {
                throw ValidationException::withMessages([
                    "form_data.$key" => 'required',
                ]);
            }
            if (($rules['unique'] ?? false) && array_key_exists($key, $data)) {
                $query = Task::where('task_type_id', $type->id)
                    ->where('form_data->'.$key, $data[$key]);
                if ($task) {
                    $query->where('id', '!=', $task->id);
                }
                if ($query->exists()) {
                    throw ValidationException::withMessages([
                        "form_data.$key" => 'unique',
                    ]);
                }
            }
        }

        $this->formSchemaService->validateData($type->schema_json, $data);
    }
}
