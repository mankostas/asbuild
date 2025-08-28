<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppointmentType;
use App\Services\FormSchemaService;
use Illuminate\Http\Request;
use App\Http\Resources\AppointmentTypeResource;
use App\Support\ListQuery;

class AppointmentTypeController extends Controller
{
    use ListQuery;

    public function __construct(private FormSchemaService $formSchemaService)
    {
    }

    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $scope = $request->query('scope', $request->user()->hasRole('SuperAdmin') ? 'all' : 'tenant');
        $query = AppointmentType::query();

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

        $result = $this->listQuery($query, $request, ['name'], ['name']);

        return AppointmentTypeResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);
        $data = $this->validateSchema($request);

        if ($request->user()->hasRole('SuperAdmin')) {
            $data['tenant_id'] = $data['tenant_id'] ?? null;
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $type = AppointmentType::create($data);
        return (new AppointmentTypeResource($type))->response()->setStatusCode(201);
    }

    public function show(AppointmentType $appointmentType)
    {
        return new AppointmentTypeResource($appointmentType);
    }

    public function update(Request $request, AppointmentType $appointmentType)
    {
        $this->ensureAdmin($request);
        if (! $request->user()->hasRole('SuperAdmin') && $appointmentType->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $data = $this->validateSchema($request, false);

        if ($request->user()->hasRole('SuperAdmin')) {
            if (array_key_exists('tenant_id', $data)) {
                $data['tenant_id'] = $data['tenant_id'];
            }
        } else {
            $data['tenant_id'] = $request->user()->tenant_id;
        }

        $appointmentType->update($data);
        return new AppointmentTypeResource($appointmentType);
    }

    public function destroy(Request $request, AppointmentType $appointmentType)
    {
        $this->ensureAdmin($request);
        if (! $request->user()->hasRole('SuperAdmin') && $appointmentType->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }
        $appointmentType->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function copyToTenant(Request $request, AppointmentType $appointmentType)
    {
        $this->ensureAdmin($request);

        $tenantId = $request->user()->hasRole('SuperAdmin')
            ? $request->input('tenant_id')
            : $request->user()->tenant_id;

        if (! $tenantId) {
            abort(400, 'tenant_id required');
        }

        $copy = $appointmentType->replicate();
        $copy->tenant_id = $tenantId;
        $copy->save();

        return (new AppointmentTypeResource($copy))
            ->response()
            ->setStatusCode(201);
    }

    protected function validateSchema(Request $request, bool $nameRequired = true): array
    {
        $rules = [
            'name' => ($nameRequired ? 'required' : 'sometimes') . '|string|max:255',
            'form_schema' => 'nullable|json',
            'fields_summary' => 'nullable|json',
            'statuses' => ($nameRequired ? 'required' : 'sometimes') . '|json',
            'tenant_id' => 'sometimes|integer',
        ];
        $validated = $request->validate($rules);

        foreach (['form_schema', 'fields_summary', 'statuses'] as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = json_decode($validated[$field], true);
            }
        }

        if (isset($validated['form_schema'])) {
            $this->formSchemaService->validate($validated['form_schema']);
        }

        return $validated;
    }
}
