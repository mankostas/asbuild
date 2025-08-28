<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\RoleUpsertRequest;
use App\Http\Resources\RoleResource;
use App\Support\ListQuery;

class RoleController extends Controller
{
    use ListQuery;

    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $scope = $request->query('scope');
        $tenantId = $request->query('tenant_id');

        if (! $request->user()->isSuperAdmin()) {
            $tenantId = $request->user()->tenant_id;
            $base = Role::where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            });
            $result = $this->listQuery($base, $request, ['name'], ['name']);
            return RoleResource::collection($result['data'])->additional([
                'meta' => $result['meta'],
            ]);
        }

        $scope = $scope ?? ($tenantId ? 'tenant' : 'all');

        $query = Role::query();

        switch ($scope) {
            case 'global':
                $query->whereNull('tenant_id');
                break;
            case 'tenant':
                $tenantId = $tenantId ?? app('tenant_id');
                if (! $tenantId) {
                    abort(400, 'Tenant ID required');
                }
                $query->where('tenant_id', $tenantId);
                break;
            case 'all':
            default:
                if ($tenantId) {
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
        $this->ensureAdmin($request);

        $data = $request->validated();

        if ($request->user()->hasRole('SuperAdmin')) {
            $data['tenant_id'] = $data['tenant_id'] ?? null;
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if ($data['name'] === 'SuperAdmin' || $data['slug'] === 'super_admin') {
            abort(403, 'SuperAdmin role cannot be created');
        }

        $role = Role::create($data);
        return (new RoleResource($role))->response()->setStatusCode(201);
    }

    public function show(Request $request, Role $role)
    {
        $this->ensureAdmin($request);

        if (! $request->user()->hasRole('SuperAdmin') && $role->tenant_id !== $request->user()->tenant_id) {
            abort(404);
        }

        return new RoleResource($role);
    }

    public function update(RoleUpsertRequest $request, Role $role)
    {
        $this->ensureAdmin($request);

        if (! $request->user()->hasRole('SuperAdmin') && $role->tenant_id !== $request->user()->tenant_id) {
            abort(404);
        }

        if ($role->name === 'SuperAdmin' || $role->slug === 'super_admin') {
            abort(403, 'Cannot modify SuperAdmin role');
        }

        $data = $request->validated();

        if ($request->user()->hasRole('SuperAdmin')) {
            $data['tenant_id'] = $data['tenant_id'] ?? $role->tenant_id;
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        if (($data['name'] ?? $role->name) === 'SuperAdmin' || ($data['slug'] ?? $role->slug) === 'super_admin') {
            abort(403, 'SuperAdmin role cannot be used');
        }

        $role->update($data);

        return new RoleResource($role);
    }

    public function destroy(Request $request, Role $role)
    {
        $this->ensureAdmin($request);

        if (! $request->user()->hasRole('SuperAdmin') && $role->tenant_id !== $request->user()->tenant_id) {
            abort(404);
        }

        if ($role->name === 'SuperAdmin' || $role->slug === 'super_admin') {
            abort(403, 'Cannot delete SuperAdmin role');
        }

        $role->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function assign(Request $request, Role $role)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ]);

        if ($role->tenant_id !== null) {
            $data['tenant_id'] = $role->tenant_id;
        } elseif (! $request->user()->hasRole('SuperAdmin')) {
            $data['tenant_id'] = $request->user()->tenant_id;
        } else {
            $data['tenant_id'] = $data['tenant_id'] ?? null;
        }

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
}

