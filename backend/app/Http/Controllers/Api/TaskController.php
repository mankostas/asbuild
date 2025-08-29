<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
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
            ->with(['type', 'assignee']);
        $result = $this->listQuery($base, $request, [], ['scheduled_at', 'created_at']);

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
        if (isset($data['task_type_id'])) {
            $type = TaskType::find($data['task_type_id']);
        }
        $data['status'] = $type && $type->statuses ? array_key_first($type->statuses) : Task::STATUS_DRAFT;
        $this->validateAgainstSchema($type, $data['form_data'] ?? []);
        $this->formSchemaService->mapAssignee($type->schema_json ?? [], $data);

        $task = Task::create($data);
        $task->watchers()->firstOrCreate(['user_id' => $request->user()->id]);
        if ($task->assignee_type === User::class && $task->assignee_id) {
            $task->watchers()->firstOrCreate(['user_id' => $task->assignee_id]);
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

        unset($data['status']);

        $typeId = $data['task_type_id'] ?? $task->task_type_id;
        $type = $typeId ? TaskType::find($typeId) : $task->type;
        $this->validateAgainstSchema($type, $data['form_data'] ?? $task->form_data ?? []);
        $this->formSchemaService->mapAssignee($type->schema_json ?? [], $data);
        $task->fill($data);
        $task->save();
        if ($task->assignee_type === User::class && $task->assignee_id) {
            $task->watchers()->firstOrCreate(['user_id' => $task->assignee_id]);
        }

        return new TaskResource($task->load('comments', 'type', 'assignee', 'watchers'));
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
        $type = $task->type;

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

    protected function validateAgainstSchema(?TaskType $type, array $data): void
    {
        if (! $type || ! $type->schema_json) {
            return;
        }

        $required = collect($type->schema_json['sections'] ?? [])
            ->flatMap(fn ($s) => collect($s['fields'] ?? [])->filter(fn ($f) => $f['required'] ?? false))
            ->map(fn ($f) => $f['key']);
        foreach ($required as $field) {
            if (! array_key_exists($field, $data)) {
                throw ValidationException::withMessages([
                    "form_data.$field" => 'required',
                ]);
            }
        }
    }
}
