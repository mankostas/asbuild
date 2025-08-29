<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskType;
use App\Services\FormSchemaService;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\TaskUpsertRequest;
use App\Support\ListQuery;

class TaskController extends Controller
{
    use ListQuery;

    public function __construct(private FormSchemaService $formSchemaService)
    {
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
        $this->formSchemaService->mapAssignee($type->form_schema ?? [], $data);

        $task = Task::create($data);
        $task->load('type', 'assignee');

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);
        return new TaskResource($task->load('comments', 'type', 'assignee'));
    }

    public function update(TaskUpsertRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validated();

        if (isset($data['status']) && $data['status'] !== $task->status) {
            $newStatus = $data['status'];
            if (! $task->canTransitionTo($newStatus)) {
                return response()->json(['message' => 'invalid_transition'], 422);
            }
            $task->status = $newStatus;
            if ($newStatus === Task::STATUS_IN_PROGRESS && ! $task->started_at) {
                $task->started_at = now();
            }
            if ($newStatus === Task::STATUS_COMPLETED && ! $task->completed_at) {
                $task->completed_at = now();
            }
        }

        $typeId = $data['task_type_id'] ?? $task->task_type_id;
        $type = $typeId ? TaskType::find($typeId) : $task->type;
        $this->validateAgainstSchema($type, $data['form_data'] ?? $task->form_data ?? []);
        $this->formSchemaService->mapAssignee($type->form_schema ?? [], $data);

        unset($data['status']);
        $task->fill($data);
        $task->save();

        return new TaskResource($task->load('comments', 'type', 'assignee'));
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        $task->delete();
        return response()->json(['message' => 'deleted']);
    }

    protected function validateAgainstSchema(?TaskType $type, array $data): void
    {
        if (! $type || ! $type->form_schema) {
            return;
        }

        $schema = $type->form_schema;
        $required = $schema['required'] ?? [];
        foreach ($required as $field) {
            if (! array_key_exists($field, $data)) {
                throw ValidationException::withMessages([
                    "form_data.$field" => 'required',
                ]);
            }
        }
    }
}
