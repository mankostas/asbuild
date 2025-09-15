<?php

namespace App\Http\Middleware;

use App\Services\AbilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Ability
{
    public function __construct(protected AbilityService $abilityService)
    {
    }

    public function handle(Request $request, Closure $next, string $code): Response
    {
        $user = $request->user();

        $tenantId = $request->attributes->get('tenant_id');
        $codes = array_filter(explode('|', $code));

        if (! $this->abilityService->userHasAnyAbility($user, $codes ?: [$code], $tenantId)) {
            return response()->json(['message' => 'forbidden'], 403);
        }

        return $next($request);
    }
}
