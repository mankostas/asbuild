<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function events(Request $request)
    {
        $data = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'team_id' => 'nullable|integer',
            'employee_id' => 'nullable|integer',
            'type_id' => 'nullable|integer',
            'status_id' => 'nullable|string',
        ]);

        $query = Appointment::where('tenant_id', $request->user()->tenant_id)
            ->with(['type', 'assignee'])
            ->whereBetween('scheduled_at', [$data['start'], $data['end']]);

        if ($request->filled('team_id')) {
            $query->where('assignee_type', Team::class)
                ->where('assignee_id', $request->query('team_id'));
        }

        if ($request->filled('employee_id')) {
            $query->where('assignee_type', User::class)
                ->where('assignee_id', $request->query('employee_id'));
        }

        if ($request->filled('type_id')) {
            $query->where('appointment_type_id', $request->query('type_id'));
        }

        if ($request->filled('status_id')) {
            $query->where('status', $request->query('status_id'));
        }

        $events = $query->get()->map(function ($a) {
            return [
                'id' => $a->id,
                'title' => $a->title ?? $a->type->name ?? 'Appointment ' . $a->id,
                'start' => $a->scheduled_at,
                'end' => $a->sla_end_at ?? $a->scheduled_at,
                'extendedProps' => [
                    'status' => $a->status,
                    'type' => $a->type->name ?? null,
                    'assignee' => $a->assignee->name ?? null,
                ],
            ];
        });

        return response()->json($events);
    }
}
