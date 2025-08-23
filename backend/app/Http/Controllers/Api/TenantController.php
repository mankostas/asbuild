<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Audit;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    protected function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin($request);
        return Tenant::all();
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin($request);
        $data = $request->validate([
            'name' => 'required|string',
            'quota_storage_mb' => 'integer',
            'features' => 'array',
        ]);
        $tenant = Tenant::create($data);
        return response()->json($tenant, 201);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $this->ensureSuperAdmin($request);
        $data = $request->validate([
            'name' => 'sometimes|string',
            'quota_storage_mb' => 'integer',
            'features' => 'array',
        ]);
        $tenant->update($data);
        return $tenant;
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
        $accessToken = $user->createToken('access-token', ['*'], now()->addMinutes(15));
        $refreshToken = $user->createToken('refresh-token', ['refresh'], now()->addDays(30));
        Audit::create([
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
