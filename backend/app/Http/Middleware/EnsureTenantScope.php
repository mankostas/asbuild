<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\PublicIdResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantScope
{
    public function __construct(private PublicIdResolver $publicIdResolver)
    {
    }

    /**
     * Enforce tenant scoping while allowing SuperAdmins to bypass checks.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenantIdentifier = $request->header('X-Tenant-ID');
        $tenantProvided = ! ($tenantIdentifier === null || $tenantIdentifier === '');
        $resolvedTenantId = $tenantProvided
            ? $this->publicIdResolver->resolve(Tenant::class, $tenantIdentifier)
            : null;

        if ($user->isSuperAdmin()) {
            if ($resolvedTenantId !== null) {
                $this->bindTenant($request, $resolvedTenantId);
            } else {
                Tenant::setCurrent(null);
                app()->forgetInstance('tenant_id');
                config(['tenant' => []]);
                config(['tenant.branding' => null]);
            }

            return $next($request);
        }

        if (! $tenantProvided || $resolvedTenantId === null || $user->tenant_id !== $resolvedTenantId) {
            return response()->json(['message' => 'forbidden'], 403);
        }

        $this->bindTenant($request, $resolvedTenantId);

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
