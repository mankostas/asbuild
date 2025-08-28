<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    protected function getTenantId(Request $request): int
    {
        if ($request->user()->hasRole('SuperAdmin')) {
            $tenantId = app('tenant_id');
            if (! $tenantId) {
                abort(400, 'Tenant ID required');
            }
            return (int) $tenantId;
        }

        return (int) $request->user()->tenant_id;
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);
        $tenantId = $this->getTenantId($request);
        return Role::where('tenant_id', $tenantId)->get();
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);
        $tenantId = $this->getTenantId($request);
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        if ($data['name'] === 'SuperAdmin') {
            abort(403, 'SuperAdmin role cannot be created');
        }
        $role = Role::create([
            'name' => $data['name'],
            'tenant_id' => $tenantId,
        ]);
        return response()->json($role, 201);
    }

    public function show(Request $request, Role $role)
    {
        $this->ensureAdmin($request);
        $tenantId = $this->getTenantId($request);
        if ($role->tenant_id !== $tenantId) {
            abort(404);
        }
        return $role;
    }

    public function update(Request $request, Role $role)
    {
        $this->ensureAdmin($request);
        $tenantId = $this->getTenantId($request);
        if ($role->tenant_id !== $tenantId) {
            abort(404);
        }
        if ($role->name === 'SuperAdmin') {
            abort(403, 'Cannot modify SuperAdmin role');
        }
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        if ($data['name'] === 'SuperAdmin') {
            abort(403, 'SuperAdmin role cannot be used');
        }
        $role->update(['name' => $data['name']]);
        return $role;
    }

    public function destroy(Request $request, Role $role)
    {
        $this->ensureAdmin($request);
        $tenantId = $this->getTenantId($request);
        if ($role->tenant_id !== $tenantId) {
            abort(404);
        }
        if ($role->name === 'SuperAdmin') {
            abort(403, 'Cannot delete SuperAdmin role');
        }
        $role->delete();
        return response()->json(['message' => 'deleted']);
    }
}

