<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskType;
use App\Services\FormSchemaService;
use Illuminate\Http\Request;
use App\Http\Resources\TaskTypeResource;
use App\Support\ListQuery;
use App\Http\Requests\TaskTypeRequest;

class TaskTypeController extends Controller
{
    use ListQuery;

    public function __construct(private FormSchemaService $formSchemaService)
    {
    }

    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $scope = $request->query('scope', $request->user()->hasRole('SuperAdmin') ? 'all' : 'tenant');
        $query = TaskType::query();

        if ($scope === 'tenant') {
            $tenantId = $request->query('tenant_id', $request->user()->tenant_id);
            $query->where('tenant_id', $tenantId);
        } elseif ($scope === 'global') {
            $query->whereNull('tenant_id');
        } else {
            if ($request->has('tenant_id')) {
                $query->where('tenant_id', $request->query('tenant_id'));
            }
        }

        $result = $this->listQuery($query, $request, ['name'], ['name']);

        return TaskTypeResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(TaskTypeRequest $request)
    {
        $this->ensureAdmin($request);
        $data = $request->validated();
        if (isset($data['schema_json'])) {
            $this->formSchemaService->validate($data['schema_json']);
        }

        if ($request->user()->hasRole('SuperAdmin')) {
            $data['tenant_id'] = $data['tenant_id'] ?? null;
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $type = TaskType::create($data);
        return (new TaskTypeResource($type))->response()->setStatusCode(201);
    }

    public function show(TaskType $taskType)
    {
        return new TaskTypeResource($taskType);
    }

    public function update(TaskTypeRequest $request, TaskType $taskType)
    {
        $this->ensureAdmin($request);
        if (! $request->user()->hasRole('SuperAdmin') && $taskType->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }
        $data = $request->validated();
        if (isset($data['schema_json'])) {
            $this->formSchemaService->validate($data['schema_json']);
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
        $this->ensureAdmin($request);
        if (! $request->user()->hasRole('SuperAdmin') && $taskType->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }
        $taskType->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function copyToTenant(Request $request, TaskType $taskType)
    {
        $this->ensureAdmin($request);

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
}
