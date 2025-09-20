<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeamUpsertRequest;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\TeamResource;
use App\Support\ListQuery;
use App\Support\PublicIdResolver;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    use ListQuery;

    public function __construct(private PublicIdResolver $publicIdResolver)
    {
    }

    protected function getTenantId(Request $request): int
    {
        if ($request->user()->isSuperAdmin()) {
            $tenantIdentifier = $request->query('tenant_id', $request->input('tenant_id', $request->header('X-Tenant-ID', app('tenant_id'))));

            if ($tenantIdentifier === null || $tenantIdentifier === '') {
                abort(400, 'Tenant ID required');
            }

            $resolved = $this->resolveTenantIdentifier($tenantIdentifier);

            if ($resolved === null) {
                abort(404, 'Tenant not found');
            }

            return $resolved;
        }

        return (int) $request->user()->tenant_id;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Team::class);

        $query = Team::query()->with(['employees', 'tenant', 'lead']);

        if ($request->user()->isSuperAdmin()) {
            if ($request->has('tenant_id')) {
                $tenantIdentifier = $request->query('tenant_id');
                $tenantId = $this->resolveTenantIdentifier($tenantIdentifier);

                if ($tenantId === null && $tenantIdentifier !== null && $tenantIdentifier !== '') {
                    $query->whereRaw('1 = 0');
                } elseif ($tenantId !== null) {
                    $query->where('tenant_id', $tenantId);
                }
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
        if ($request->user()->isSuperAdmin()) {
            $data['tenant_id'] = $data['tenant_id'] ?? $tenantId;
        } else {
            $data['tenant_id'] = $tenantId;
        }

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

        $identifiers = $this->validatedEmployeeIdentifiers($request);
        $employeeIds = $this->resolveUserIdentifiers($identifiers);

        if (count($employeeIds) !== count($identifiers)) {
            throw ValidationException::withMessages([
                'employee_ids' => ['One or more employees are invalid.'],
            ]);
        }

        $employeeIds = User::whereIn('id', $employeeIds)
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->all();

        $team->employees()->sync($employeeIds);

        return new TeamResource($team->load(['employees', 'tenant', 'lead']));
    }

    protected function resolveTenantIdentifier(mixed $identifier): ?int
    {
        if ($identifier instanceof Tenant) {
            return (int) $identifier->getKey();
        }

        if (is_int($identifier)) {
            return $identifier;
        }

        if (is_string($identifier)) {
            $identifier = trim($identifier);

            if ($identifier === '') {
                return null;
            }
        }

        if ($identifier === null || $identifier === '') {
            return null;
        }

        return $this->publicIdResolver->resolve(Tenant::class, $identifier);
    }

    /**
     * @return array<int, string>
     */
    protected function validatedEmployeeIdentifiers(Request $request): array
    {
        $input = $request->all();

        if (isset($input['employee_ids']) && is_array($input['employee_ids'])) {
            $input['employee_ids'] = array_map(static function ($value) {
                if (is_string($value)) {
                    return trim($value);
                }

                return (string) $value;
            }, $input['employee_ids']);
        }

        $data = validator($input, [
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['string'],
        ])->validate();

        return $this->normalizeIdentifiers($data['employee_ids']);
    }

    /**
     * @param  array<int, string|int|null>  $identifiers
     * @return array<int, int>
     */
    protected function resolveUserIdentifiers(array $identifiers): array
    {
        $resolved = [];

        foreach ($identifiers as $identifier) {
            if ($identifier instanceof User) {
                $resolved[] = (int) $identifier->getKey();
                continue;
            }

            if (is_int($identifier)) {
                $resolved[] = $identifier;
                continue;
            }

            if (is_string($identifier) && $identifier !== '') {
                $id = $this->publicIdResolver->resolve(User::class, $identifier);

                if ($id !== null) {
                    $resolved[] = $id;
                }
            }
        }

        return array_values(array_unique($resolved));
    }

    /**
     * @param  array<int, string|null>  $identifiers
     * @return array<int, string>
     */
    protected function normalizeIdentifiers(array $identifiers): array
    {
        $normalized = [];

        foreach ($identifiers as $identifier) {
            if (is_string($identifier)) {
                $identifier = trim($identifier);
            }

            if ($identifier === null || $identifier === '') {
                continue;
            }

            $normalized[] = is_string($identifier) ? $identifier : (string) $identifier;
        }

        return array_values(array_unique($normalized));
    }
}
