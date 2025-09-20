<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\RoleUpsertRequest;
use App\Http\Resources\RoleResource;
use App\Support\ListQuery;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    use ListQuery;

    public function index(Request $request)
    {
        Gate::authorize('roles.view');

        $scope = $request->query('scope');
        $tenantId = $request->query('tenant_id');

        if ($tenantId !== null) {
            $tenantId = $this->resolveTenantId($tenantId);
            $scope = 'tenant';
        }

        if (! $request->user()->isSuperAdmin()) {
            $tenantId = $request->user()->tenant_id;
            $userLevel = $request->user()->roleLevel($tenantId);
            $base = Role::with('tenant:id,public_id')
                ->withCount('users')
                ->where('tenant_id', $tenantId)
                ->where('level', '>=', $userLevel);
            $result = $this->listQuery($base, $request, ['name'], ['name']);
            return RoleResource::collection($result['data'])->additional([
                'meta' => $result['meta'],
            ]);
        }

        $scope = $scope ?? ($tenantId ? 'tenant' : 'all');

        $query = Role::query()->with('tenant:id,public_id')->withCount('users');

        switch ($scope) {
            case 'global':
                $query->whereNull('tenant_id');
                break;
            case 'tenant':
                $tenantId = $this->resolveTenantId($tenantId ?? app('tenant_id'));
                if ($tenantId === null) {
                    abort(400, 'Tenant ID required');
                }
                $query->where('tenant_id', $tenantId);
                break;
            case 'all':
            default:
                if ($tenantId !== null) {
                    $query->where(function ($q) use ($tenantId) {
                        $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
                    });
                }
                break;
        }

        $result = $this->listQuery($query, $request, ['name'], ['name']);

        return RoleResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(RoleUpsertRequest $request)
    {
        $this->authorize('create', Role::class);

        $data = $request->validated();

        if ($request->user()->isSuperAdmin()) {
            $data['tenant_id'] = $data['tenant_id'] ?? null;
        } else {
            $tenantId = $request->user()->tenant_id;
            $userLevel = $request->user()->roleLevel($tenantId);
            $level = $data['level'] ?? $userLevel;
            if ($level < $userLevel) {
                abort(403);
            }
            $data['tenant_id'] = $tenantId;
            $data['level'] = $level;

            $tenant = Tenant::find($tenantId);
            $allowed = $tenant ? $tenant->allowedAbilities() : [];
            $data['abilities'] = array_values(array_intersect($data['abilities'] ?? [], $allowed));
        }

        if ($data['name'] === 'SuperAdmin' || $data['slug'] === 'super_admin') {
            abort(403, 'SuperAdmin role cannot be created');
        }

        $role = Role::create($data);
        $role->load('tenant');
        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    public function show(Request $request, Role $role)
    {
        $this->authorize('view', $role);

        if (! $request->user()->isSuperAdmin()) {
            if ($role->level < $request->user()->roleLevel($request->user()->tenant_id)) {
                abort(403);
            }
        }

        return new RoleResource($role->loadMissing('tenant'));
    }

    public function update(RoleUpsertRequest $request, Role $role)
    {
        $this->authorize('update', $role);

        if (! $request->user()->isSuperAdmin()) {
            $userLevel = $request->user()->roleLevel($request->user()->tenant_id);
            if ($role->level < $userLevel) {
                abort(403);
            }
        }

        if ($role->name === 'SuperAdmin' || $role->slug === 'super_admin') {
            abort(403, 'Cannot modify SuperAdmin role');
        }

        $data = $request->validated();

        if ($request->user()->isSuperAdmin()) {
            $data['tenant_id'] = $data['tenant_id'] ?? $role->tenant_id;
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
            $userLevel = $request->user()->roleLevel($request->user()->tenant_id);
            $level = $data['level'] ?? $role->level;
            if ($level < $userLevel) {
                abort(403);
            }
            $data['level'] = $level;

            $tenant = Tenant::find($request->user()->tenant_id);
            $allowed = $tenant ? $tenant->allowedAbilities() : [];
            $data['abilities'] = array_values(array_intersect($data['abilities'] ?? [], $allowed));
        }

        if (($data['name'] ?? $role->name) === 'SuperAdmin' || ($data['slug'] ?? $role->slug) === 'super_admin') {
            abort(403, 'SuperAdmin role cannot be used');
        }

        $role->update($data);

        return new RoleResource($role->load('tenant'));
    }

    public function destroy(Request $request, Role $role)
    {
        $this->authorize('delete', $role);

        if (! $request->user()->isSuperAdmin()) {
            if ($role->level < $request->user()->roleLevel($request->user()->tenant_id)) {
                abort(403);
            }
        }

        if ($role->name === 'SuperAdmin' || $role->slug === 'super_admin') {
            abort(403, 'Cannot delete SuperAdmin role');
        }

        $role->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function assign(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        $data = $request->validate([
            'user_id' => ['required', 'string', 'ulid', Rule::exists('users', 'public_id')],
            'tenant_id' => ['nullable', 'string', 'ulid', Rule::exists('tenants', 'public_id')],
        ]);

        if (! $request->user()->isSuperAdmin() && $role->level < $request->user()->roleLevel($request->user()->tenant_id)) {
            abort(403);
        }

        if ($role->tenant_id !== null) {
            $data['tenant_id'] = $role->tenant_id;
        } elseif (! $request->user()->isSuperAdmin()) {
            $data['tenant_id'] = $request->user()->tenant_id;
        } else {
            $data['tenant_id'] = $this->resolveTenantId($data['tenant_id'] ?? null);
        }

        $userId = User::where('public_id', $data['user_id'])->value('id');

        if ($userId === null) {
            throw ValidationException::withMessages([
                'user_id' => __('The selected user is invalid.'),
            ]);
        }

        $data['user_id'] = $userId;

        DB::table('role_user')->updateOrInsert(
            [
                'role_id' => $role->id,
                'user_id' => $data['user_id'],
                'tenant_id' => $data['tenant_id'],
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json(['message' => 'assigned']);
    }

    protected function resolveTenantId(mixed $identifier): ?int
    {
        if ($identifier === null || $identifier === '') {
            return null;
        }

        if (is_string($identifier) && ! ctype_digit($identifier)) {
            $resolved = Tenant::where('public_id', $identifier)->value('id');

            if ($resolved === null) {
                throw ValidationException::withMessages([
                    'tenant_id' => __('The selected tenant is invalid.'),
                ]);
            }

            return (int) $resolved;
        }

        return (int) $identifier;
    }
}

