<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Http\Resources\EmployeeResource;

class EmployeeController extends Controller
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

        $employees = User::where('tenant_id', $tenantId)
            ->with('roles')
            ->paginate($request->query('per_page', 15));

        return EmployeeResource::collection($employees->items())->additional([
            'meta' => [
                'page' => $employees->currentPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'roles' => 'array|nullable',
            'roles.*' => 'string',
        ]);

        if (! empty($data['roles']) && in_array('SuperAdmin', $data['roles'], true)) {
            abort(403, 'SuperAdmin role cannot be assigned');
        }

        $password = Str::random(config('security.password.min_length'));

        $tenantId = $this->getTenantId($request);
        Tenant::findOrFail($tenantId);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'tenant_id' => $tenantId,
            'password' => Hash::make($password),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        if (! empty($data['roles'])) {
            $roles = collect($data['roles'])
                ->map(fn ($name) => Role::firstOrCreate(['name' => $name, 'tenant_id' => $tenantId])->id);
            $roleData = $roles->mapWithKeys(fn ($id) => [$id => ['tenant_id' => $tenantId]]);
            $user->roles()->sync($roleData);
        }

        Mail::raw("You have been invited. Temporary password: {$password}", function ($m) use ($user) {
            $m->to($user->email)->subject('Invitation');
        });

        return (new EmployeeResource($user->load('roles')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, User $employee)
    {
        $this->ensureAdmin($request);

        $tenantId = $this->getTenantId($request);
        if ($employee->tenant_id !== $tenantId) {
            abort(404);
        }

        return new EmployeeResource($employee->load('roles'));
    }

    public function update(Request $request, User $employee)
    {
        $this->ensureAdmin($request);

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

    public function destroy(Request $request, User $employee)
    {
        $this->ensureAdmin($request);

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

