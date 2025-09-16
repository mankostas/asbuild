<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskStatus;
use Illuminate\Http\Request;
use App\Http\Resources\TaskStatusResource;
use App\Services\StatusFlowService;
use App\Support\ListQuery;
use App\Http\Requests\TaskStatusUpsertRequest;

class TaskStatusController extends Controller
{
    use ListQuery;

    public function index(Request $request)
    {
        $this->authorize('viewAny', TaskStatus::class);

        $scope = $request->query('scope', $request->user()->hasRole('SuperAdmin') ? 'all' : 'tenant');
        $query = TaskStatus::query()->withCount('tasks');

        if ($scope === 'tenant') {
            $tenantId = $request->query('tenant_id', $request->header('X-Tenant-ID', $request->user()->tenant_id));
            $query->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            });
        } elseif ($scope === 'global') {
            $query->whereNull('tenant_id');
        } else {
            if ($request->has('tenant_id')) {
                $tenantId = $request->query('tenant_id');

                if ($tenantId === 'super_admin') {
                    $query->whereNull('tenant_id');
                } else {
                    $query->where('tenant_id', $tenantId);
                }
            }
        }

        $result = $this->listQuery($query, $request, ['name'], ['name']);

        return TaskStatusResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(TaskStatusUpsertRequest $request)
    {
        $this->authorize('create', TaskStatus::class);

        $data = $request->validated();

        if ($request->user()->hasRole('SuperAdmin')) {
            $data['tenant_id'] = $data['tenant_id'] ?? null;
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $status = TaskStatus::create($data);
        return (new TaskStatusResource($status))->response()->setStatusCode(201);
    }

    public function show(TaskStatus $taskStatus)
    {
        $this->authorize('view', $taskStatus);

        return new TaskStatusResource($taskStatus);
    }

    public function update(TaskStatusUpsertRequest $request, TaskStatus $taskStatus)
    {
        $this->authorize('update', $taskStatus);

        $data = $request->validated();

        if ($request->user()->hasRole('SuperAdmin')) {
            $data['tenant_id'] = array_key_exists('tenant_id', $data)
                ? $data['tenant_id']
                : $taskStatus->tenant_id;
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $taskStatus->update($data);
        return new TaskStatusResource($taskStatus);
    }

    public function destroy(Request $request, TaskStatus $taskStatus)
    {
        $this->authorize('delete', $taskStatus);

        $taskStatus->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function copyToTenant(Request $request, TaskStatus $taskStatus)
    {
        $this->authorize('view', $taskStatus);

        $tenantId = $request->user()->hasRole('SuperAdmin')
            ? $request->input('tenant_id')
            : $request->user()->tenant_id;

        if (! $tenantId) {
            abort(400, 'tenant_id required');
        }

        $copy = $taskStatus->replicate();
        $copy->tenant_id = $tenantId;
        $copy->save();

        return (new TaskStatusResource($copy))
            ->response()
            ->setStatusCode(201);
    }

    public function transitions(TaskStatus $taskStatus, StatusFlowService $flow)
    {
        $this->authorize('view', $taskStatus);

        $names = $flow->allowedTransitions($taskStatus->name);
        $query = TaskStatus::query()->whereIn('name', $names);

        if ($taskStatus->tenant_id) {
            $query->where('tenant_id', $taskStatus->tenant_id);
        } else {
            $query->whereNull('tenant_id');
        }

        $statuses = $query->get();
        return TaskStatusResource::collection($statuses);
    }
}
