<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
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

        $query = Task::where('tenant_id', $request->user()->tenant_id)
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
            $query->where('task_type_id', $request->query('type_id'));
        }

        if ($request->filled('status_id')) {
            $query->where('status', $request->query('status_id'));
        }

        $events = $query->get()->map(function ($t) {
            return [
                'id' => $t->id,
                'title' => $t->title ?? $t->type->name ?? 'Task ' . $t->id,
                'start' => $t->scheduled_at,
                'end' => $t->sla_end_at ?? $t->scheduled_at,
                'extendedProps' => [
                    'status' => $t->status,
                    'type' => $t->type->name ?? null,
                    'assignee' => $t->assignee->name ?? null,
                ],
            ];
        });

        return response()->json($events);
    }
}
