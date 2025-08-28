<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Services\FormSchemaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function __construct(private FormSchemaService $formSchemaService)
    {
    }
    public function index(Request $request)
    {
        $appointments = Appointment::where('tenant_id', $request->user()->tenant_id)
            ->with('type')
            ->get();
        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validate([
            'scheduled_at' => 'nullable|date',
            'sla_start_at' => 'nullable|date',
            'sla_end_at' => 'nullable|date',
            'kau_notes' => 'nullable|string',
            'appointment_type_id' => 'nullable|exists:appointment_types,id',
            'form_data' => 'nullable|array',
            'assignee' => 'nullable|array',
            'assignee.kind' => 'required_with:assignee|in:team,employee',
            'assignee.id' => 'required_with:assignee|integer',
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;

        $type = null;
        if (isset($data['appointment_type_id'])) {
            $type = AppointmentType::find($data['appointment_type_id']);
        }
        $data['status'] = $type && $type->statuses ? array_key_first($type->statuses) : Appointment::STATUS_DRAFT;
        $this->validateAgainstSchema($type, $data['form_data'] ?? []);
        $this->formSchemaService->mapAssignee($type->form_schema ?? [], $data);

        $appointment = Appointment::create($data);

        return response()->json($appointment, 201);
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        return response()->json($appointment->load('photos', 'comments', 'type'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $typeId = $request->input('appointment_type_id', $appointment->appointment_type_id);
        $type = $typeId ? AppointmentType::find($typeId) : null;
        $allowedStatuses = collect($type->statuses ?? Appointment::$transitions)
            ->flatMap(function ($next, $current) {
                return array_merge([$current], $next);
            })
            ->unique()
            ->all();

        $data = $request->validate([
            'scheduled_at' => 'nullable|date',
            'sla_start_at' => 'nullable|date',
            'sla_end_at' => 'nullable|date',
            'kau_notes' => 'nullable|string',
            'appointment_type_id' => 'nullable|exists:appointment_types,id',
            'form_data' => 'nullable|array',
            'assignee' => 'nullable|array',
            'assignee.kind' => 'required_with:assignee|in:team,employee',
            'assignee.id' => 'required_with:assignee|integer',
            'status' => [
                'nullable',
                Rule::in($allowedStatuses),
            ],
        ]);

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

        $type = null;
        if (isset($data['appointment_type_id'])) {
            $type = AppointmentType::find($data['appointment_type_id']);
        } else {
            $type = $appointment->type;
        }
        $this->validateAgainstSchema($type, $data['form_data'] ?? $appointment->form_data ?? []);
        $this->formSchemaService->mapAssignee($type->form_schema ?? [], $data);

        unset($data['status']);
        $appointment->fill($data);
        $appointment->save();

        return response()->json($appointment);
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
