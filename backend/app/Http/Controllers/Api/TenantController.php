<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ManagesTenantUsers;
use App\Http\Requests\TenantUpsertRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\TenantSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\ListQuery;
use App\Http\Resources\TenantOwnerResource;

class TenantController extends Controller
{
    use ListQuery;
    use ManagesTenantUsers;

    protected function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);
        $query = Tenant::query();

        if ($request->filled('tenant_id')) {
            $tenantId = $request->query('tenant_id');
            $query->where('id', $tenantId);
        }

        $result = $this->listQuery($query, $request, ['name'], ['name']);

        $result['data'] = array_map(function ($tenant) {
            return $tenant->makeVisible('feature_abilities');
        }, $result['data']);

        return response()->json($result);
    }

    public function store(TenantUpsertRequest $request)
    {
        $this->ensureSuperAdmin($request);
        $data = $request->validated();
        $tenant = DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'name' => $data['name'],
                'quota_storage_mb' => $data['quota_storage_mb'] ?? null,
                'features' => $data['features'] ?? ['tasks'],
                'feature_abilities' => $data['feature_abilities'] ?? [],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            $user = User::create([
                'name' => $data['user_name'],
                'email' => $data['user_email'],
                'tenant_id' => $tenant->id,
                'password' => Hash::make(Str::random(config('security.password.min_length'))),
            ]);

            $roleId = $tenant->roles()->where('slug', 'tenant')->value('id');
            if ($roleId) {
                $user->roles()->attach($roleId, ['tenant_id' => $tenant->id]);
            }

            Password::sendResetLink(['email' => $user->email]);

            return $tenant;
        });

        app(TenantSetupService::class)->createDefaultTaskStatuses($tenant);

        return response()->json($tenant->load('roles'), 201);
    }

    public function show(Request $request, Tenant $tenant)
    {
        $this->ensureSuperAdmin($request);
        return $tenant->makeVisible('feature_abilities');
    }

    public function update(TenantUpsertRequest $request, Tenant $tenant)
    {
        $this->ensureSuperAdmin($request);
        $data = $request->validated();
        $tenant->update($data);
        return $tenant->load('roles');
    }

    public function destroy(Request $request, Tenant $tenant)
    {
        $this->ensureSuperAdmin($request);
        $tenant->delete();
        return response()->noContent();
    }

    public function impersonate(Request $request, Tenant $tenant)
    {
        $this->ensureSuperAdmin($request);
        $user = User::where('tenant_id', $tenant->id)->firstOrFail();
        $user->tokens()->delete();
        $accessToken = $user->createToken('impersonation', ['*'], now()->addMinutes(15));
        $refreshToken = $user->createToken('impersonation-refresh', ['refresh'], now()->addDays(30));
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'impersonate',
            'target_id' => $user->id,
            'meta' => ['tenant_id' => $tenant->id],
        ]);
        return response()->json([
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'user' => $user->load('roles'),
            'impersonator_id' => $request->user()->id,
        ]);
    }

    protected function ensureTenantRouteScope(Request $request, Tenant $tenant): void
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        $tenantId = $request->attributes->get('tenant_id') ?? $request->header('X-Tenant-ID');

        if ((int) $tenant->id !== (int) $tenantId) {
            abort(404);
        }
    }

    protected function resolveTenantOwner(Tenant $tenant): User
    {
        $tenantRoleId = $tenant->roles()->where('slug', 'tenant')->value('id');

        if ($tenantRoleId) {
            $owner = User::whereHas('roles', function ($query) use ($tenantRoleId, $tenant) {
                $query->where('roles.id', $tenantRoleId)
                    ->where('role_user.tenant_id', $tenant->id);
            })->first();

            if ($owner) {
                return $owner;
            }
        }

        return User::where('tenant_id', $tenant->id)
            ->where(function ($query) {
                $query->whereNull('type')
                    ->orWhere('type', 'tenant');
            })
            ->orderBy('id')
            ->firstOrFail();
    }

    public function owner(Request $request, Tenant $tenant)
    {
        $this->ensureTenantRouteScope($request, $tenant);

        $owner = $this->resolveTenantOwner($tenant)->load('roles');

        return new TenantOwnerResource($owner);
    }

    public function ownerPasswordReset(Request $request, Tenant $tenant)
    {
        $this->ensureTenantRouteScope($request, $tenant);

        $owner = $this->resolveTenantOwner($tenant);
        $this->ensureUserIsNotSuperAdmin($owner);

        $this->dispatchPasswordReset($owner);

        return response()->json(['status' => 'ok']);
    }

    public function ownerResendInvite(Request $request, Tenant $tenant)
    {
        $this->ensureTenantRouteScope($request, $tenant);

        $owner = $this->resolveTenantOwner($tenant);
        $this->ensureUserIsNotSuperAdmin($owner);

        $this->dispatchInvite($owner);

        return response()->json(['status' => 'ok']);
    }

    public function ownerResetEmail(Request $request, Tenant $tenant)
    {
        $this->ensureTenantRouteScope($request, $tenant);

        $owner = $this->resolveTenantOwner($tenant);
        $this->ensureUserIsNotSuperAdmin($owner);

        $data = $request->validate([
            'email' => 'required|email|unique:users,email,' . $owner->id,
        ]);

        $this->resetUserEmail($owner, $data['email']);

        return new TenantOwnerResource($owner->fresh()->load('roles'));
    }
}
