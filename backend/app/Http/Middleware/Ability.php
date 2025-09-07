<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Ability
{
    public function handle(Request $request, Closure $next, string $code): Response
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $tenantId = $request->attributes->get('tenant_id', $user->tenant_id);

        $roles = $user->rolesForTenant($tenantId)
            ->merge($user->roles()->wherePivotNull('tenant_id')->get());

        $abilities = $roles->pluck('abilities')->flatten()->filter()->unique()->all();

        if (! in_array('*', $abilities)) {
            $codes = explode('|', $code);
            $allowed = false;
            foreach ($codes as $abilityCode) {
                if (in_array($abilityCode, $abilities)) {
                    $allowed = true;
                    break;
                }
                $prefix = explode('.', $abilityCode)[0] . '.manage';
                if (in_array($prefix, $abilities)) {
                    $allowed = true;
                    break;
                }
            }

            if (! $allowed) {
                return response()->json(['message' => 'forbidden'], 403);
            }
        }

        return $next($request);
    }
}
