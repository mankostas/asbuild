<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    protected function getTenantId(Request $request): int
    {
        if ($request->user()->hasRole('SuperAdmin')) {
            $tenantId = app('tenant_id');
            if (! $tenantId) {
                abort(400, 'Tenant ID required');
            }
            return (int) $tenantId;
        }

        return (int) $request->user()->tenant_id;
    }

    public function assignees(Request $request)
    {
        $tenantId = $this->getTenantId($request);
        $type = $request->query('type', 'all');

        $results = collect();

        if ($type === 'all' || $type === 'teams') {
            $teams = Team::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($team) => [
                    'id' => $team->id,
                    'label' => $team->name,
                    'kind' => 'team',
                ]);
            $results = $results->merge($teams);
        }

        if ($type === 'all' || $type === 'employees') {
            $employees = User::where('tenant_id', $tenantId)
                ->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'tenant'))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($user) => [
                    'id' => $user->id,
                    'label' => $user->name,
                    'kind' => 'employee',
                ]);
            $results = $results->merge($employees);
        }

        return $results->sortBy('label')->values();
    }

    public function abilities(Request $request)
    {
        if ($request->boolean('forTenant')) {
            $tenantId = $this->getTenantId($request);
            $tenant = Tenant::find($tenantId);

            return $tenant?->allowedAbilities() ?? [];
        }

        return collect(config('abilities'))->values()->all();
    }

    public function features()
    {
        return collect(config('feature_map'))
            ->map(fn ($data, $slug) => [
                'slug' => $slug,
                'label' => $data['label'],
            ])
            ->values()
            ->all();
    }
}
