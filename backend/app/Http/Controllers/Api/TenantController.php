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
use App\Support\PublicIdResolver;
use Database\Seeders\DefaultFeatureRolesSeeder;
use Illuminate\Validation\ValidationException;

class TenantController extends Controller
{
    use ListQuery;
    use ManagesTenantUsers;

    public function __construct(private PublicIdResolver $publicIdResolver)
    {
    }

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
            $tenantIdentifier = $request->query('tenant_id');
            $tenantId = $this->resolveTenantIdentifier($tenantIdentifier);

            if ($tenantId === null && $tenantIdentifier !== null && $tenantIdentifier !== '') {
                $query->whereRaw('1 = 0');
            } elseif ($tenantId !== null) {
                $query->where('id', $tenantId);
            }
        }

        $trashed = $request->query('trashed');
        if ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === 'only') {
            $query->onlyTrashed();
        }

        $archived = $request->query('archived');
        if (! ($trashed === 'only' && $archived === null)) {
            if (in_array($archived, ['only', 'true', '1'], true)) {
                $query->whereNotNull('archived_at');
            } elseif ($archived !== 'all') {
                $query->whereNull('archived_at');
            }
        }

        $result = $this->listQuery($query, $request, ['name'], ['name']);

        $result['data'] = array_map(function ($tenant) {
            return $this->transformTenant($tenant);
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
                'status' => $data['status'] ?? 'active',
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

            if ($data['notify_owner'] ?? true) {
                Password::sendResetLink(['email' => $user->email]);
            }

            return $tenant;
        });

        $tenant->refresh();
        DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant, $tenant->selectedFeatureAbilities());
        $tenant->refresh();

        app(TenantSetupService::class)->createDefaultTaskStatuses($tenant);

        return response()->json($this->transformTenant($tenant), 201);
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

        if (array_key_exists('features', $data) || array_key_exists('feature_abilities', $data)) {
            $tenant->refresh();
            DefaultFeatureRolesSeeder::syncDefaultRolesForFeatures($tenant, $tenant->selectedFeatureAbilities());
            $tenant->refresh();
        }

        return $this->transformTenant($tenant);
    }

    public function destroy(Request $request, Tenant $tenant)
    {
        $this->ensureSuperAdmin($request);
        $tenant->delete();
        return response()->noContent();
    }

    public function archive(Request $request, Tenant $tenant)
    {
        $this->ensureSuperAdmin($request);

        if ($tenant->archived_at === null) {
            $tenant->archived_at = now();
            $tenant->save();
        }

        return $this->transformTenant($tenant);
    }

    public function unarchive(Request $request, Tenant $tenant)
    {
        $this->ensureSuperAdmin($request);

        if ($tenant->archived_at !== null) {
            $tenant->archived_at = null;
            $tenant->save();
        }

        return $this->transformTenant($tenant);
    }

    public function restore(Request $request, string $tenant)
    {
        $this->ensureSuperAdmin($request);

        $tenantId = $this->resolveTenantIdentifier($tenant);

        if ($tenantId === null) {
            abort(404);
        }

        $model = Tenant::withTrashed()->findOrFail($tenantId);
        if ($model->trashed()) {
            $model->restore();
        }

        if ($model->archived_at !== null) {
            $model->archived_at = null;
            $model->save();
        }

        return $this->transformTenant($model);
    }

    public function bulkArchive(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $identifiers = $this->validatedTenantIdentifiers($request);
        $tenantIds = $this->resolveTenantIdentifiers($identifiers);

        if (count($tenantIds) !== count($identifiers)) {
            throw ValidationException::withMessages([
                'ids' => ['One or more tenants are invalid.'],
            ]);
        }

        $tenants = Tenant::query()->whereIn('id', $tenantIds)->get();

        $now = now();

        foreach ($tenants as $tenant) {
            if ($tenant->trashed() || $tenant->archived_at !== null) {
                continue;
            }

            $tenant->archived_at = $now;
            $tenant->save();
        }

        return $tenants->map(function (Tenant $tenant) {
            return $this->transformTenant($tenant);
        })->values();
    }

    public function bulkDestroy(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $identifiers = $this->validatedTenantIdentifiers($request);
        $tenantIds = $this->resolveTenantIdentifiers($identifiers);

        if (count($tenantIds) !== count($identifiers)) {
            throw ValidationException::withMessages([
                'ids' => ['One or more tenants are invalid.'],
            ]);
        }

        $tenants = Tenant::query()->whereIn('id', $tenantIds)->get();

        if ($tenants->isNotEmpty()) {
            Tenant::query()->whereKey($tenants->modelKeys())->delete();
        }

        $trashed = Tenant::withTrashed()->whereIn('id', $tenants->modelKeys())->get();

        return $trashed->map(function (Tenant $tenant) {
            return $this->transformTenant($tenant);
        })->values();
    }

    public function bulkRestore(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $identifiers = $this->validatedTenantIdentifiers($request);
        $tenantIds = $this->resolveTenantIdentifiers($identifiers);

        if (count($tenantIds) !== count($identifiers)) {
            throw ValidationException::withMessages([
                'ids' => ['One or more tenants are invalid.'],
            ]);
        }

        $tenants = Tenant::withTrashed()->whereIn('id', $tenantIds)->get();

        foreach ($tenants as $tenant) {
            if ($tenant->trashed()) {
                $tenant->restore();
            }

            if ($tenant->archived_at !== null) {
                $tenant->archived_at = null;
                $tenant->save();
            }
        }

        return $tenants->map(function (Tenant $tenant) {
            return $this->transformTenant($tenant);
        })->values();
    }

    protected function transformTenant(Tenant $tenant): Tenant
    {
        $tenant->loadMissing('roles');

        return $tenant->makeVisible('feature_abilities');
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

        $tenantIdentifier = $request->attributes->get('tenant_id') ?? $request->header('X-Tenant-ID');
        $resolvedId = is_int($tenantIdentifier)
            ? $tenantIdentifier
            : $this->resolveTenantIdentifier($tenantIdentifier);

        if ($resolvedId === null || (int) $tenant->id !== (int) $resolvedId) {
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

    /**
     * @return array<int, string>
     */
    protected function validatedTenantIdentifiers(Request $request): array
    {
        $input = $request->all();

        if (isset($input['ids']) && is_array($input['ids'])) {
            $input['ids'] = array_map(static fn ($value) => is_string($value) ? $value : (string) $value, $input['ids']);
        }

        $data = validator($input, [
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
        ])->validate();

        return $this->normalizeIdentifiers($data['ids']);
    }

    /**
     * @param  array<int, string|int|null>  $identifiers
     * @return array<int, int>
     */
    protected function resolveTenantIdentifiers(array $identifiers): array
    {
        $resolved = [];

        foreach ($identifiers as $identifier) {
            $tenantId = $this->resolveTenantIdentifier($identifier);

            if ($tenantId !== null) {
                $resolved[] = $tenantId;
            }
        }

        return array_values(array_unique($resolved));
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
