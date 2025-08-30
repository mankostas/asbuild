<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskSlaPolicy;
use Carbon\Carbon;

class TaskSlaService
{
    public function apply(Task $task): void
    {
        if (! $task->task_type_id || ! $task->priority) {
            return;
        }
        $policy = TaskSlaPolicy::where('task_type_id', $task->task_type_id)
            ->where('priority', $task->priority)
            ->first();
        if (! $policy || ! $policy->resolve_within_mins) {
            return;
        }
        $start = $task->sla_start_at ? $task->sla_start_at->copy() : Carbon::now();
        $task->sla_end_at = $this->addBusinessMinutes($start, $policy->resolve_within_mins, $policy->calendar_json ?? []);
    }

    protected function addBusinessMinutes(Carbon $start, int $minutes, array $calendar): Carbon
    {
        $date = $start->copy();
        $hours = $calendar['hours'] ?? [
            'mon' => ['09:00', '17:00'],
            'tue' => ['09:00', '17:00'],
            'wed' => ['09:00', '17:00'],
            'thu' => ['09:00', '17:00'],
            'fri' => ['09:00', '17:00'],
        ];
        $holidays = $calendar['holidays'] ?? [];
        while ($minutes > 0) {
            $dow = strtolower($date->format('D'));
            if (in_array($date->toDateString(), $holidays, true)) {
                $date->addDay()->startOfDay();
                continue;
            }
            $range = $hours[$dow] ?? null;
            if (! $range) {
                $date->addDay()->startOfDay();
                continue;
            }
            [$startStr, $endStr] = $range;
            $workStart = $date->copy()->setTimeFromTimeString($startStr);
            $workEnd = $date->copy()->setTimeFromTimeString($endStr);
            if ($date->lt($workStart)) {
                $date = $workStart->copy();
            }
            if ($date->gte($workEnd)) {
                $date->addDay()->startOfDay();
                continue;
            }
            $available = $date->diffInMinutes($workEnd, false);
            $add = min($available, $minutes);
            $date->addMinutes($add);
            $minutes -= $add;
            if ($date->gte($workEnd)) {
                $date->addDay()->startOfDay();
            }
        }
        return $date;
    }
}
