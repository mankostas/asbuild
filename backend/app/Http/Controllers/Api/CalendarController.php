<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function events(Request $request)
    {
        $data = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'assignee_id' => 'nullable|integer',
            'type_id' => 'nullable|integer',
            'status_id' => 'nullable|string',
        ]);

        $query = Task::where('tenant_id', $request->user()->tenant_id)
            ->with(['type', 'assignee'])
            ->whereBetween('scheduled_at', [$data['start'], $data['end']]);

        if ($request->filled('assignee_id')) {
            $query->where('assigned_user_id', $request->query('assignee_id'));
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
