<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantScope
{
    /**
     * Enforce tenant scoping while allowing SuperAdmins to bypass checks.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenantId = $request->header('X-Tenant-ID');

        if ($user->isSuperAdmin()) {
            if ($tenantId) {
                $this->bindTenant($request, (int) $tenantId);
            }
            return $next($request);
        }

        if (! $tenantId || $user->tenant_id !== (int) $tenantId) {
            return response()->json(['message' => 'forbidden'], 403);
        }

        $this->bindTenant($request, (int) $tenantId);

        return $next($request);
    }

    protected function bindTenant(Request $request, int $tenantId): void
    {
        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            Tenant::setCurrent($tenant);
            $request->attributes->set('tenant_id', $tenantId);
            app()->instance('tenant_id', $tenantId);
            $settings = DB::table('tenant_settings')
                ->where('tenant_id', $tenantId)
                ->pluck('value', 'key')
                ->toArray();
            config(['tenant' => $settings]);

            $branding = DB::table('brandings')
                ->where('tenant_id', $tenantId)
                ->first();
            if (! $branding) {
                $branding = DB::table('brandings')->whereNull('tenant_id')->first();
            }
            config(['tenant.branding' => $branding]);
        }
    }
}
