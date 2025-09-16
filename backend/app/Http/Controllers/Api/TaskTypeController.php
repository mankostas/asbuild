<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskTypeRequest;
use App\Http\Resources\TaskTypeResource;
use App\Models\TaskType;
use App\Services\FormSchemaService;
use App\Services\StatusFlowService;
use App\Support\ListQuery;
use App\Support\TenantDefaults;
use Illuminate\Http\Request;

class TaskTypeController extends Controller
{
    use ListQuery;

    public function __construct(private FormSchemaService $formSchemaService) {}

    public function index(Request $request)
    {
        // Task types are no longer versioned, so we only eager load the tenant
        // and append a count of related tasks
        $query = TaskType::query()->with(['tenant'])->withCount('tasks');

        if ($request->user()->hasRole('SuperAdmin')) {
            $scope = $request->query('scope', 'all');

            if ($scope === 'tenant') {
                $query->where('tenant_id', $request->query('tenant_id'));
            } elseif ($scope === 'global') {
                $query->whereNull('tenant_id');
            } elseif ($request->has('tenant_id')) {
                $query->where('tenant_id', $request->query('tenant_id'));
            }
        } else {
            $query->where('tenant_id', $request->user()->tenant_id);
        }

        $result = $this->listQuery($query, $request, ['name'], ['name']);

        return TaskTypeResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function options(Request $request)
    {
        $tenantId = $request->attributes->get('tenant_id') ?? auth()->user()?->tenant_id;

        $types = TaskType::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($types);
    }

    public function store(TaskTypeRequest $request)
    {
        $data = $request->validated();
        if (isset($data['schema_json'])) {
            $this->formSchemaService->validate($data['schema_json']);
        }

        if (! array_key_exists('statuses', $data)) {
            $data['statuses'] = array_fill_keys(
                array_column(TenantDefaults::TASK_STATUSES, 'slug'),
                []
            );
        }

        if (! array_key_exists('status_flow_json', $data)) {
            $data['status_flow_json'] = StatusFlowService::DEFAULT_TRANSITIONS;
        }

        if ($request->user()->hasRole('SuperAdmin')) {
            $data['tenant_id'] = $data['tenant_id'] ?? null;
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $type = TaskType::create($data);

        return (new TaskTypeResource($type))->response()->setStatusCode(201);
    }

    public function show(Request $request, TaskType $taskType)
    {
        if (! $request->user()->hasRole('SuperAdmin') && $taskType->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        return new TaskTypeResource($taskType);
    }

    public function update(TaskTypeRequest $request, TaskType $taskType)
    {
        if (! $request->user()->hasRole('SuperAdmin') && $taskType->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }
        $data = $request->validated();
        if (isset($data['schema_json'])) {
            $this->formSchemaService->validate($data['schema_json']);
        }

        if (! array_key_exists('statuses', $data)) {
            $data['statuses'] = $taskType->statuses ?? array_fill_keys(
                array_column(TenantDefaults::TASK_STATUSES, 'slug'),
                []
            );
        }

        if (! array_key_exists('status_flow_json', $data)) {
            $data['status_flow_json'] = $taskType->status_flow_json ?? StatusFlowService::DEFAULT_TRANSITIONS;
        }

        if ($request->user()->hasRole('SuperAdmin')) {
            if (array_key_exists('tenant_id', $data)) {
                $data['tenant_id'] = $data['tenant_id'];
            }
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $taskType->update($data);

        return new TaskTypeResource($taskType);
    }

    public function destroy(Request $request, TaskType $taskType)
    {
        if (! $request->user()->hasRole('SuperAdmin') && $taskType->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }
        $taskType->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $query = TaskType::query()->whereIn('id', $data['ids']);

        if (! $request->user()->hasRole('SuperAdmin')) {
            $query->where('tenant_id', $request->user()->tenant_id);
        }

        $query->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function copyToTenant(Request $request, TaskType $taskType)
    {
        $tenantId = $request->user()->hasRole('SuperAdmin')
            ? $request->input('tenant_id')
            : $request->user()->tenant_id;

        if (! $tenantId) {
            abort(400, 'tenant_id required');
        }

        $copy = $taskType->replicate();
        $copy->tenant_id = $tenantId;
        $copy->save();

        return (new TaskTypeResource($copy))
            ->response()
            ->setStatusCode(201);
    }

    public function bulkCopyToTenant(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'tenant_id' => 'nullable|integer',
        ]);

        $tenantId = $data['tenant_id'] ?? $request->user()->tenant_id;

        if (! $tenantId) {
            abort(400, 'tenant_id required');
        }

        if (! $request->user()->hasRole('SuperAdmin') && $tenantId !== $request->user()->tenant_id) {
            abort(403);
        }

        if (! $request->user()->hasRole('SuperAdmin') && $request->user()->rolesForTenant($tenantId)->isEmpty()) {
            abort(403);
        }

        $types = TaskType::query()->whereIn('id', $data['ids'])->get();

        $copies = collect();

        foreach ($types as $type) {
            if (! $request->user()->hasRole('SuperAdmin') && $type->tenant_id !== $request->user()->tenant_id) {
                continue;
            }

            $copy = $type->replicate();
            $copy->tenant_id = $tenantId;
            $copy->save();

            $copies->push($copy);
        }

        return TaskTypeResource::collection($copies)
            ->response()
            ->setStatusCode(201);
    }

    public function validateSchema(Request $request)
    {
        $data = $request->validate([
            'schema_json' => 'required|array',
            'form_data' => 'array',
        ]);

        $this->formSchemaService->validate($data['schema_json']);
        $this->formSchemaService->validateData($data['schema_json'], $data['form_data'] ?? []);

        return response()->json(['message' => 'ok']);
    }

    public function previewValidate(Request $request, TaskType $taskType)
    {
        if (! $request->user()->hasRole('SuperAdmin') && $taskType->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $data = $request->validate([
            'schema_json' => 'required|array',
            'form_data' => 'array',
        ]);

        $this->formSchemaService->validate($data['schema_json']);
        $this->formSchemaService->validateData($data['schema_json'], $data['form_data'] ?? []);

        return response()->json(['message' => 'ok']);
    }

    public function export(Request $request, TaskType $taskType)
    {
        if (! $request->user()->hasRole('SuperAdmin') && $taskType->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        return response()->json($taskType->toArray());
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'schema_json' => 'array',
        ]);

        if (isset($data['schema_json'])) {
            $this->formSchemaService->validate($data['schema_json']);
        }

        $data['tenant_id'] = $request->user()->hasRole('SuperAdmin')
            ? ($data['tenant_id'] ?? null)
            : $request->user()->tenant_id;

        $type = TaskType::create($data);

        return (new TaskTypeResource($type))->response()->setStatusCode(201);
    }
}
