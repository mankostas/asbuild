<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppointmentType;
use App\Services\FormSchemaService;
use Illuminate\Http\Request;
use App\Http\Resources\AppointmentTypeResource;
use App\Support\ListQuery;
use App\Http\Requests\TypeUpsertRequest;

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

    public function store(TypeUpsertRequest $request)
    {
        $this->ensureAdmin($request);
        $data = $request->validated();
        if (isset($data['form_schema'])) {
            $this->formSchemaService->validate($data['form_schema']);
        }

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

    public function update(TypeUpsertRequest $request, AppointmentType $appointmentType)
    {
        $this->ensureAdmin($request);
        if (! $request->user()->hasRole('SuperAdmin') && $appointmentType->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }
        $data = $request->validated();
        if (isset($data['form_schema'])) {
            $this->formSchemaService->validate($data['form_schema']);
        }

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
}
