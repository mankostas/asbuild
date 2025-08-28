<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            $teams = Team::where('tenant_id', $tenantId)->get()
                ->map(fn ($team) => ['id' => $team->id, 'label' => $team->name, 'kind' => 'team']);
            $results = $results->merge($teams);
        }

        if ($type === 'all' || $type === 'employees') {
            $employees = User::where('tenant_id', $tenantId)->get()
                ->map(fn ($user) => ['id' => $user->id, 'label' => $user->name, 'kind' => 'employee']);
            $results = $results->merge($employees);
        }

        return $results->values();
    }
}
