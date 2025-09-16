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

    protected function getTenantId(Request $request): int
    {
        if ($request->user()->isSuperAdmin()) {
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
        $this->authorize('viewAny', Team::class);

        $query = Team::query()->with(['employees', 'tenant', 'lead']);

        if ($request->user()->isSuperAdmin()) {
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
        $this->authorize('create', Team::class);

        $data = $request->validated();
        $tenantId = $this->getTenantId($request);
        $data['tenant_id'] = $request->user()->isSuperAdmin() ? ($data['tenant_id'] ?? $tenantId) : $tenantId;

        if (isset($data['lead_id'])) {
            $leadId = User::where('id', $data['lead_id'])
                ->where('tenant_id', $data['tenant_id'])
                ->value('id');
            if (! $leadId) {
                abort(422, 'Invalid team lead.');
            }
            $data['lead_id'] = $leadId;
        }

        $team = Team::create($data);

        return (new TeamResource($team->load(['employees', 'tenant', 'lead'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Team $team)
    {
        $tenantId = $this->getTenantId($request);
        if ($team->tenant_id !== $tenantId) {
            abort(404);
        }

        $this->authorize('view', $team);

        return new TeamResource($team->load(['employees', 'tenant', 'lead']));
    }

    public function update(TeamUpsertRequest $request, Team $team)
    {
        $tenantId = $this->getTenantId($request);
        if ($team->tenant_id !== $tenantId) {
            abort(404);
        }

        $this->authorize('update', $team);

        $data = $request->validated();
        $data['tenant_id'] = $team->tenant_id;

        if (isset($data['lead_id'])) {
            $leadId = User::where('id', $data['lead_id'])
                ->where('tenant_id', $data['tenant_id'])
                ->value('id');
            if (! $leadId) {
                abort(422, 'Invalid team lead.');
            }
            $data['lead_id'] = $leadId;
        }

        $team->update($data);

        return new TeamResource($team->load(['employees', 'tenant', 'lead']));
    }

    public function destroy(Request $request, Team $team)
    {
        $tenantId = $this->getTenantId($request);
        if ($team->tenant_id !== $tenantId) {
            abort(404);
        }

        $this->authorize('delete', $team);

        $team->delete();

        return response()->noContent();
    }

    public function syncEmployees(Request $request, Team $team)
    {
        $tenantId = $this->getTenantId($request);
        if ($team->tenant_id !== $tenantId) {
            abort(404);
        }

        $this->authorize('manageMembers', $team);

        $data = $request->validate([
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $employeeIds = User::whereIn('id', $data['employee_ids'])
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->all();

        $team->employees()->sync($employeeIds);

        return new TeamResource($team->load(['employees', 'tenant', 'lead']));
    }
}
