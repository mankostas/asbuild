<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ManagesTenantUsers;
use App\Models\User;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Resources\EmployeeResource;
use App\Support\ListQuery;

class EmployeeController extends Controller
{
    use ListQuery;
    use ManagesTenantUsers;

    protected function guardManageableEmployee(Request $request, User $employee): int|string
    {
        $tenantId = $this->getTenantId($request);
        $this->ensureEmployeeTenant($employee, $tenantId);
        $this->ensureUserIsNotSuperAdmin($employee);

        return $tenantId;
    }

    protected function getTenantId(Request $request, bool $allowNull = false): int|string|null
    {
        if ($request->user()->hasRole('SuperAdmin')) {
            $tenantId = app()->bound('tenant_id')
                ? app('tenant_id')
                : ($request->query('tenant_id')
                    ?? $request->input('tenant_id')
                    ?? $request->header('X-Tenant-ID'));

            if (! $tenantId && ! $allowNull) {
                abort(400, 'Tenant ID required');
            }

            if ($tenantId === 'super_admin') {
                return 'super_admin';
            }

            return $tenantId ? (int) $tenantId : null;
        }

        return (int) $request->user()->tenant_id;
    }

    protected function ensureEmployeeTenant(User $employee, int|string $tenantId): void
    {
        if ($employee->type !== 'employee') {
            abort(404);
        }

        if (is_numeric($tenantId) && $employee->tenant_id !== (int) $tenantId) {
            abort(404);
        }

        if ($tenantId === 'super_admin' && ! $employee->hasRole('SuperAdmin')) {
            abort(404);
        }
    }

    public function index(Request $request)
    {
        $tenantId = $this->getTenantId($request, true);

        $base = User::where('type', 'employee')
            ->with('roles');

        if (is_numeric($tenantId)) {
            $base->where('tenant_id', (int) $tenantId);
        } elseif ($tenantId === 'super_admin') {
            $base->whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'));
        }
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
            'type' => 'employee',
        ]);

        if (! empty($data['roles'])) {
            $roles = collect($data['roles'])
                ->map(fn ($name) => Role::firstOrCreate(['name' => $name, 'tenant_id' => $tenantId])->id);
            $roleData = $roles->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tenantId]]);
            $user->roles()->sync($roleData);
        }

        $this->dispatchInvite($user);

        return (new EmployeeResource($user->load('roles')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, User $employee)
    {
        $tenantId = $this->getTenantId($request);
        $this->ensureEmployeeTenant($employee, $tenantId);

        return new EmployeeResource($employee->load('roles'));
    }

    public function update(Request $request, User $employee)
    {
        $tenantId = $this->guardManageableEmployee($request, $employee);

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

    public function impersonate(Request $request, User $employee)
    {
        $tenantId = $this->getTenantId($request);
        $this->ensureEmployeeTenant($employee, $tenantId);

        if ($employee->hasRole('SuperAdmin')) {
            abort(403, 'Cannot impersonate a SuperAdmin');
        }

        $employee->tokens()->delete();
        $accessToken = $employee->createToken('impersonation', ['*'], now()->addMinutes(15));
        $refreshToken = $employee->createToken('impersonation-refresh', ['refresh'], now()->addDays(30));

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'impersonate',
            'target_id' => $employee->id,
            'meta' => ['tenant_id' => $tenantId],
        ]);

        return response()->json([
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'user' => $employee->load('roles'),
            'impersonator_id' => $request->user()->id,
        ]);
    }

    public function resendInvite(Request $request, User $employee)
    {
        $this->guardManageableEmployee($request, $employee);

        $this->dispatchInvite($employee);

        return response()->json(['status' => 'ok']);
    }

    public function sendPasswordReset(Request $request, User $employee)
    {
        $this->guardManageableEmployee($request, $employee);

        $this->dispatchPasswordReset($employee);

        return response()->json(['status' => 'ok']);
    }

    public function resetEmail(Request $request, User $employee)
    {
        $this->guardManageableEmployee($request, $employee);

        $data = $request->validate([
            'email' => 'required|email|unique:users,email,' . $employee->id,
        ]);

        $this->resetUserEmail($employee, $data['email']);

        return new EmployeeResource($employee->fresh());
    }

    public function toggleStatus(Request $request, User $employee)
    {
        $this->guardManageableEmployee($request, $employee);

        $employee->status = $employee->status === 'active' ? 'inactive' : 'active';
        $employee->save();

        return new EmployeeResource($employee);
    }

    public function destroy(Request $request, User $employee)
    {
        $this->guardManageableEmployee($request, $employee);

        $employee->delete();
        return response()->noContent();
    }
}

