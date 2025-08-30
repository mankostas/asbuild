<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Task;
use Carbon\Carbon;
use App\Services\Notifier;

class SlaService
{
    public function __construct(private Notifier $notifier)
    {
    }

    public function check(): void
    {
        $now = Carbon::now();
        $tomorrow = $now->copy()->addDay();

        $approaching = Task::whereNull('completed_at')
            ->whereNotNull('sla_end_at')
            ->whereBetween('sla_end_at', [$now, $tomorrow])
            ->get();

        foreach ($approaching as $task) {
            $this->notify($task, 'approaching');
        }

        $overdue = Task::whereNull('completed_at')
            ->whereNotNull('sla_end_at')
            ->where('sla_end_at', '<', $now)
            ->get();

        foreach ($overdue as $task) {
            $this->notify($task, 'overdue');
        }
    }

    protected function notify(Task $task, string $status): void
    {
        $user = $task->user;
        if (! $user) {
            return;
        }

        $message = match ($status) {
            'approaching' => 'Task ' . $task->id . ' SLA due soon.',
            'overdue' => 'Task ' . $task->id . ' SLA overdue.',
            default => null,
        };

        if (! $message) {
            return;
        }

        $link = '/tasks/' . $task->id;

        $exists = Notification::where('user_id', $user->id)
            ->where('category', 'sla')
            ->where('message', $message)
            ->whereDate('created_at', Carbon::today())
            ->exists();

        if (! $exists) {
            $this->notifier->send($user, 'sla', $message, $link);
        }
    }
}

