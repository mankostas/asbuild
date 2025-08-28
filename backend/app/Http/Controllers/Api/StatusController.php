<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $scope = $request->query('scope', $request->user()->hasRole('SuperAdmin') ? 'all' : 'tenant');
        $query = Status::query();

        if ($scope === 'tenant') {
            $tenantId = $request->query('tenant_id', $request->user()->tenant_id);
            $query->where('tenant_id', $tenantId);
        } elseif ($scope === 'global') {
            $query->whereNull('tenant_id');
        } else {
            if ($request->has('tenant_id')) {
                $query->where('tenant_id', $request->query('tenant_id'));
            }
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);
        $data = $request->validate([
            'name' => 'required|string',
            'tenant_id' => 'sometimes|integer',
        ]);

        if ($request->user()->hasRole('SuperAdmin')) {
            $data['tenant_id'] = $data['tenant_id'] ?? null;
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $status = Status::create($data);
        return response()->json($status, 201);
    }

    public function show(Status $status)
    {
        return $status;
    }

    public function update(Request $request, Status $status)
    {
        $this->ensureAdmin($request);
        if (! $request->user()->hasRole('SuperAdmin') && $status->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }
        $data = $request->validate([
            'name' => 'required|string',
            'tenant_id' => 'sometimes|integer',
        ]);

        if ($request->user()->hasRole('SuperAdmin')) {
            if (array_key_exists('tenant_id', $data)) {
                $data['tenant_id'] = $data['tenant_id'];
            }
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $status->update($data);
        return $status;
    }

    public function destroy(Request $request, Status $status)
    {
        $this->ensureAdmin($request);
        if (! $request->user()->hasRole('SuperAdmin') && $status->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }
        $status->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function copyToTenant(Request $request, Status $status)
    {
        $this->ensureAdmin($request);

        $tenantId = $request->user()->hasRole('SuperAdmin')
            ? $request->input('tenant_id')
            : $request->user()->tenant_id;

        if (! $tenantId) {
            abort(400, 'tenant_id required');
        }

        $copy = $status->replicate();
        $copy->tenant_id = $tenantId;
        $copy->save();

        return response()->json($copy, 201);
    }
}

