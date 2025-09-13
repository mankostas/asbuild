<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeamUpsertRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\TeamResource;
use App\Support\ListQuery;

class TeamController extends Controller
{
    use ListQuery;

    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    protected function getTenantId(Request $request): int
    {
        if ($request->user()->hasRole('SuperAdmin')) {
            $tenantId = $request->query('tenant_id', $request->input('tenant_id', $request->header('X-Tenant-ID', app('tenant_id'))));
            if (! $tenantId) {
                abort(400, 'Tenant ID required');
            }
            return (int) $tenantId;
        }

        return (int) $request->user()->tenant_id;
    }

    public function index(Request $request)
    {
        $query = Team::query()->with(['employees', 'tenant']);

        if ($request->user()->hasRole('SuperAdmin')) {
            if ($request->has('tenant_id')) {
                $query->where('tenant_id', $request->query('tenant_id'));
            }
        } else {
            $query->where('tenant_id', $request->user()->tenant_id);
        }

        $result = $this->listQuery($query, $request, ['name'], ['name']);

        return TeamResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(TeamUpsertRequest $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validated();
        $tenantId = $this->getTenantId($request);
        $data['tenant_id'] = $request->user()->hasRole('SuperAdmin') ? ($data['tenant_id'] ?? $tenantId) : $tenantId;

        $team = Team::create($data);

        return (new TeamResource($team->load(['employees', 'tenant'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Team $team)
    {
        $tenantId = $this->getTenantId($request);
        if ($team->tenant_id !== $tenantId) {
            abort(404);
        }

        return new TeamResource($team->load(['employees', 'tenant']));
    }

    public function update(TeamUpsertRequest $request, Team $team)
    {
        $this->ensureAdmin($request);

        $tenantId = $this->getTenantId($request);
        if ($team->tenant_id !== $tenantId) {
            abort(404);
        }

        $data = $request->validated();
        $data['tenant_id'] = $team->tenant_id;
        $team->update($data);

        return new TeamResource($team->load(['employees', 'tenant']));
    }

    public function destroy(Request $request, Team $team)
    {
        $this->ensureAdmin($request);

        $tenantId = $this->getTenantId($request);
        if ($team->tenant_id !== $tenantId) {
            abort(404);
        }

        $team->delete();

        return response()->noContent();
    }

    public function syncEmployees(Request $request, Team $team)
    {
        $this->ensureAdmin($request);

        $tenantId = $this->getTenantId($request);
        if ($team->tenant_id !== $tenantId) {
            abort(404);
        }

        $data = $request->validate([
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $employeeIds = User::whereIn('id', $data['employee_ids'])
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->all();

        $team->employees()->sync($employeeIds);

        return new TeamResource($team->load(['employees', 'tenant']));
    }
}
