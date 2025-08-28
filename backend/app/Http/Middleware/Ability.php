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

        if (! in_array('*', $abilities) && ! in_array($code, $abilities)) {
            return response()->json(['message' => 'forbidden'], 403);
        }

        return $next($request);
    }
}
