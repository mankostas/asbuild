<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = Appointment::where('tenant_id', $request->user()->tenant_id)->get();
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
        ]);

        $data['tenant_id'] = $request->user()->tenant_id;
        $data['status'] = Appointment::STATUS_DRAFT;

        $appointment = Appointment::create($data);

        return response()->json($appointment, 201);
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        return response()->json($appointment->load('photos', 'comments'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'scheduled_at' => 'nullable|date',
            'sla_start_at' => 'nullable|date',
            'sla_end_at' => 'nullable|date',
            'kau_notes' => 'nullable|string',
            'status' => [
                'nullable',
                Rule::in([
                    Appointment::STATUS_ASSIGNED,
                    Appointment::STATUS_IN_PROGRESS,
                    Appointment::STATUS_COMPLETED,
                    Appointment::STATUS_REJECTED,
                    Appointment::STATUS_REDO,
                ]),
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
}
