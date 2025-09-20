<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaskStatus;
use App\Models\Tenant;
use App\Support\PublicIdResolver;
use Illuminate\Http\Request;
use App\Http\Resources\TaskStatusResource;
use App\Services\StatusFlowService;
use App\Support\ListQuery;
use App\Http\Requests\TaskStatusUpsertRequest;
use Illuminate\Validation\ValidationException;

class TaskStatusController extends Controller
{
    use ListQuery;

    public function __construct(private PublicIdResolver $publicIdResolver)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', TaskStatus::class);

        $scope = $request->query('scope', $request->user()->hasRole('SuperAdmin') ? 'all' : 'tenant');
        $query = TaskStatus::query()->withCount('tasks');

        if ($scope === 'tenant') {
            $tenantIdentifier = $request->query(
                'tenant_id',
                $request->header('X-Tenant-ID', $request->user()->tenant_id)
            );
            $tenantId = $this->resolveTenantId($tenantIdentifier);

            if ($tenantId === null) {
                abort(400, 'tenant_id required');
            }

            $query->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            });
        } elseif ($scope === 'global') {
            $query->whereNull('tenant_id');
        } else {
            if ($request->has('tenant_id')) {
                $tenantIdentifier = $request->query('tenant_id');

                if ($tenantIdentifier === 'super_admin') {
                    $query->whereNull('tenant_id');
                } else {
                    $tenantId = $this->resolveTenantId($tenantIdentifier);
                    $query->where('tenant_id', $tenantId);
                }
            }
        }

        $query->with('tenant:id,public_id');

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
        $status->load('tenant');

        return (new TaskStatusResource($status))->response()->setStatusCode(201);
    }

    public function show(TaskStatus $taskStatus)
    {
        $this->authorize('view', $taskStatus);

        return new TaskStatusResource($taskStatus->loadMissing('tenant'));
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

        return new TaskStatusResource($taskStatus->load('tenant'));
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

        $tenantIdentifier = $request->user()->hasRole('SuperAdmin')
            ? $request->input('tenant_id')
            : $request->user()->tenant_id;

        $tenantId = $this->resolveTenantId($tenantIdentifier);

        if (! $tenantId) {
            abort(400, 'tenant_id required');
        }

        $copy = $taskStatus->replicate();
        $copy->public_id = TaskStatus::generatePublicId();
        $copy->tenant_id = $tenantId;
        $copy->save();
        $copy->load('tenant');

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
        return TaskStatusResource::collection($statuses->load('tenant'));
    }

    protected function resolveTenantId(mixed $identifier): ?int
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        $resolved = $this->publicIdResolver->resolve(Tenant::class, $identifier);

        if ($resolved === null) {
            throw ValidationException::withMessages([
                'tenant_id' => __('The selected tenant is invalid.'),
            ]);
        }

        return $resolved;
    }
}
