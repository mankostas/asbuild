<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use App\Models\Team;
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
            ->with(['type', 'typeVersion', 'assignee', 'status'])
            ->withCount(['comments', 'attachments', 'watchers', 'subtasks']);

        if ($type = $request->query('type')) {
            $base->where('task_type_id', $type);
        }

        if ($status = $request->query('status')) {
            $base->where('status_slug', $status);
        }

        if ($assignee = $request->query('assignee')) {
            $base->where('assignee_type', User::class)->where('assignee_id', $assignee);
        }

        if ($team = $request->query('team')) {
            $base->where('assignee_type', Team::class)->where('assignee_id', $team);
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
            $base->where('assignee_type', User::class)->where('assignee_id', $request->user()->id);
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

        $data['tenant_id'] = $request->user()->tenant_id;

        $type = null;
        $version = null;
        if (isset($data['task_type_id'])) {
            $type = TaskType::find($data['task_type_id']);
            $version = $type?->currentVersion;
            if ($version) {
                $data['task_type_version_id'] = $version->id;
            }
        }
        $data['status'] = $version && $version->statuses ? array_key_first($version->statuses) : Task::STATUS_DRAFT;
        $this->validateAgainstSchema($version, $data['form_data'] ?? [], null);
        $this->formSchemaService->mapAssignee($version->schema_json ?? [], $data);
        $this->formSchemaService->mapReviewer($version->schema_json ?? [], $data);
        if (isset($data['form_data'])) {
            $this->formSchemaService->sanitizeRichText($version->schema_json ?? [], $data['form_data']);
        }

        $task = Task::create($data);
        $task->watchers()->firstOrCreate(['user_id' => $request->user()->id]);
        if ($task->assignee_type === User::class && $task->assignee_id) {
            $task->watchers()->firstOrCreate(['user_id' => $task->assignee_id]);
        }
        $task->load('type', 'typeVersion', 'assignee', 'watchers');

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);
        return new TaskResource($task->load('comments', 'type', 'typeVersion', 'assignee', 'watchers'));
    }

    public function update(TaskUpsertRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validated();

        unset($data['status']);

        $typeId = $data['task_type_id'] ?? $task->task_type_id;
        $type = $typeId ? TaskType::find($typeId) : $task->type;
        $version = $type?->currentVersion;
        if ($version) {
            $data['task_type_version_id'] = $version->id;
        }
        $this->validateAgainstSchema($version, $data['form_data'] ?? $task->form_data ?? [], $task);
        $this->formSchemaService->mapAssignee($version->schema_json ?? [], $data);
        $this->formSchemaService->mapReviewer($version->schema_json ?? [], $data);
        if (isset($data['form_data'])) {
            $this->formSchemaService->sanitizeRichText($version->schema_json ?? [], $data['form_data']);
        }
        $task->fill($data);
        $task->save();
        if ($task->assignee_type === User::class && $task->assignee_id) {
            $task->watchers()->firstOrCreate(['user_id' => $task->assignee_id]);
        }

        return new TaskResource($task->load('comments', 'type', 'typeVersion', 'assignee', 'watchers'));
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
        $type = $task->typeVersion;

        if (! $this->statusFlow->canTransition($task->status, $next, $type)) {
            return response()->json(['message' => 'invalid_transition'], 422);
        }

        $reason = $this->statusFlow->checkConstraints($task, $next);
        if ($reason) {
            return response()->json(['message' => 'constraint_failed', 'reason' => $reason], 422);
        }

        $task->status = $next;
        if ($next === Task::STATUS_IN_PROGRESS && ! $task->started_at) {
            $task->started_at = now();
        }
        if ($next === Task::STATUS_COMPLETED && ! $task->completed_at) {
            $task->completed_at = now();
        }
        $task->save();

        return new TaskResource($task->load('type', 'assignee'));
    }

    protected function validateAgainstSchema($version, array $data, ?Task $task = null): void
    {
        if (! $version || ! $version->schema_json) {
            return;
        }

        $this->formSchemaService->assertCanEdit($version->schema_json, $data, auth()->user());

        $fields = collect($version->schema_json['sections'] ?? [])
            ->flatMap(fn ($s) => $s['fields'] ?? []);
        $logic = $this->formSchemaService->evaluateLogic($version->schema_json, $data);
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
                $query = Task::where('task_type_id', $version->task_type_id)
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

        $this->formSchemaService->validateData($version->schema_json, $data);
    }
}
