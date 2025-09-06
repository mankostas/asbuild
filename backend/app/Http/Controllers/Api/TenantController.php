<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\ListQuery;

class TenantController extends Controller
{
    use ListQuery;

    protected function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);
        $result = $this->listQuery(Tenant::query(), $request, ['name'], ['name']);
        return response()->json($result);
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);
        $data = $request->validate([
            'name' => 'required|string',
            'quota_storage_mb' => 'integer',
            'features' => 'array',
            'feature_abilities' => 'array',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'user_name' => 'required|string',
            'user_email' => 'required|email',
        ]);
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

        return response()->json($tenant->load('roles'), 201);
    }

    public function show(Request $request, Tenant $tenant)
    {
        $this->ensureSuperAdmin($request);
        return $tenant;
    }

    public function update(Request $request, Tenant $tenant)
    {
        $this->ensureSuperAdmin($request);
        $data = $request->validate([
            'name' => 'sometimes|string',
            'quota_storage_mb' => 'integer',
            'features' => 'array',
            'feature_abilities' => 'array',
            'phone' => 'sometimes|nullable|string',
            'address' => 'sometimes|nullable|string',
        ]);
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
}
