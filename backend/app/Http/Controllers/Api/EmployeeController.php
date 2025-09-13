<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Http\Resources\EmployeeResource;
use App\Support\ListQuery;

class EmployeeController extends Controller
{
    use ListQuery;

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
        $tenantId = $this->getTenantId($request);

        $base = User::where('tenant_id', $tenantId)
            ->with('roles');
        $result = $this->listQuery($base, $request, ['name', 'email', 'department'], ['name', 'email', 'department']);

        return EmployeeResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'department' => 'nullable|string',
            'roles' => 'array|nullable',
            'roles.*' => 'string',
        ]);

        if (! empty($data['roles']) && in_array('SuperAdmin', $data['roles'], true)) {
            abort(403, 'SuperAdmin role cannot be assigned');
        }

        $tenantId = $this->getTenantId($request);
        Tenant::findOrFail($tenantId);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'tenant_id' => $tenantId,
            'password' => Hash::make(Str::random(config('security.password.min_length'))),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'department' => $data['department'] ?? null,
        ]);

        if (! empty($data['roles'])) {
            $roles = collect($data['roles'])
                ->map(fn ($name) => Role::firstOrCreate(['name' => $name, 'tenant_id' => $tenantId])->id);
            $roleData = $roles->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tenantId]]);
            $user->roles()->sync($roleData);
        }

        Password::sendResetLink(['email' => $user->email]);

        return (new EmployeeResource($user->load('roles')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, User $employee)
    {
        $tenantId = $this->getTenantId($request);
        if ($employee->tenant_id !== $tenantId) {
            abort(404);
        }

        return new EmployeeResource($employee->load('roles'));
    }

    public function update(Request $request, User $employee)
    {
        $tenantId = $this->getTenantId($request);
        if ($employee->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($employee->hasRole('SuperAdmin')) {
            abort(403, 'Cannot modify a SuperAdmin');
        }

        $data = $request->validate([
            'name' => 'sometimes|string',
            'phone' => 'sometimes|string',
            'address' => 'sometimes|string',
            'department' => 'sometimes|string',
            'roles' => 'sometimes|array',
            'roles.*' => 'string',
        ]);

        if (isset($data['name'])) {
            $employee->name = $data['name'];
        }
        if (isset($data['phone'])) {
            $employee->phone = $data['phone'];
        }
        if (isset($data['address'])) {
            $employee->address = $data['address'];
        }
        if (isset($data['department'])) {
            $employee->department = $data['department'];
        }
        $employee->save();

        if (array_key_exists('roles', $data)) {
            if (in_array('SuperAdmin', $data['roles'], true)) {
                abort(403, 'SuperAdmin role cannot be assigned');
            }
            $roles = collect($data['roles'])
                ->map(fn ($name) => Role::firstOrCreate(['name' => $name, 'tenant_id' => $tenantId])->id);
            $roleData = $roles->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tenantId]]);
            $employee->roles()->sync($roleData);
        }

        return new EmployeeResource($employee->load('roles'));
    }

    public function toggleStatus(Request $request, User $employee)
    {
        $tenantId = $this->getTenantId($request);
        if ($employee->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($employee->hasRole('SuperAdmin')) {
            abort(403, 'Cannot modify a SuperAdmin');
        }

        $employee->status = $employee->status === 'active' ? 'inactive' : 'active';
        $employee->save();

        return new EmployeeResource($employee);
    }

    public function destroy(Request $request, User $employee)
    {
        $tenantId = $this->getTenantId($request);
        if ($employee->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($employee->hasRole('SuperAdmin')) {
            abort(403, 'Cannot delete a SuperAdmin');
        }

        $employee->delete();
        return response()->noContent();
    }
}

