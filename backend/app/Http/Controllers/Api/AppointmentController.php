<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Services\FormSchemaService;
use App\Http\Resources\AppointmentResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\AppointmentUpsertRequest;
use App\Support\ListQuery;

class AppointmentController extends Controller
{
    use ListQuery;

    public function __construct(private FormSchemaService $formSchemaService)
    {
    }
    public function index(Request $request)
    {
        $base = Appointment::where('tenant_id', $request->user()->tenant_id)
            ->with(['type', 'assignee']);
        $result = $this->listQuery($base, $request, [], ['scheduled_at', 'created_at']);

        return AppointmentResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(AppointmentUpsertRequest $request)
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validated();

        $data['tenant_id'] = $request->user()->tenant_id;

        $type = null;
        if (isset($data['appointment_type_id'])) {
            $type = AppointmentType::find($data['appointment_type_id']);
        }
        $data['status'] = $type && $type->statuses ? array_key_first($type->statuses) : Appointment::STATUS_DRAFT;
        $this->validateAgainstSchema($type, $data['form_data'] ?? []);
        $this->formSchemaService->mapAssignee($type->form_schema ?? [], $data);

        $appointment = Appointment::create($data);
        $appointment->load('type', 'assignee');

        return (new AppointmentResource($appointment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        return new AppointmentResource($appointment->load('photos', 'comments', 'type', 'assignee'));
    }

    public function update(AppointmentUpsertRequest $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $data = $request->validated();

        if (isset($data['status']) && $data['status'] !== $appointment->status) {
            $newStatus = $data['status'];
            if (! $appointment->canTransitionTo($newStatus)) {
                return response()->json(['message' => 'invalid_transition'], 422);
            }
            if ($newStatus === Appointment::STATUS_COMPLETED && ! $appointment->photos()->exists()) {
                return response()->json(['message' => 'photos_required'], 422);
            }
            $appointment->status = $newStatus;
            if ($newStatus === Appointment::STATUS_IN_PROGRESS && ! $appointment->started_at) {
                $appointment->started_at = now();
            }
            if ($newStatus === Appointment::STATUS_COMPLETED && ! $appointment->completed_at) {
                $appointment->completed_at = now();
            }
        }

        $typeId = $data['appointment_type_id'] ?? $appointment->appointment_type_id;
        $type = $typeId ? AppointmentType::find($typeId) : $appointment->type;
        $this->validateAgainstSchema($type, $data['form_data'] ?? $appointment->form_data ?? []);
        $this->formSchemaService->mapAssignee($type->form_schema ?? [], $data);

        unset($data['status']);
        $appointment->fill($data);
        $appointment->save();

        return new AppointmentResource($appointment->load('photos', 'comments', 'type', 'assignee'));
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);
        $appointment->delete();
        return response()->json(['message' => 'deleted']);
    }

    protected function validateAgainstSchema(?AppointmentType $type, array $data): void
    {
        if (! $type || ! $type->form_schema) {
            return;
        }

        $schema = $type->form_schema;
        $required = $schema['required'] ?? [];
        foreach ($required as $field) {
            if (! array_key_exists($field, $data)) {
                throw ValidationException::withMessages([
                    "form_data.$field" => 'required',
                ]);
            }
        }
    }
}
