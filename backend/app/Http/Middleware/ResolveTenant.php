<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID');

        if ($tenantId) {
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
        } else {
            $branding = DB::table('brandings')->whereNull('tenant_id')->first();
            config(['tenant.branding' => $branding]);
        }

        return $next($request);
    }
}
