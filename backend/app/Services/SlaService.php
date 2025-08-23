<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Notification;
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

        $approaching = Appointment::whereNull('completed_at')
            ->whereNotNull('sla_end_at')
            ->whereBetween('sla_end_at', [$now, $tomorrow])
            ->get();

        foreach ($approaching as $appointment) {
            $this->notify($appointment, 'approaching');
        }

        $overdue = Appointment::whereNull('completed_at')
            ->whereNotNull('sla_end_at')
            ->where('sla_end_at', '<', $now)
            ->get();

        foreach ($overdue as $appointment) {
            $this->notify($appointment, 'overdue');
        }
    }

    protected function notify(Appointment $appointment, string $status): void
    {
        $user = $appointment->user;
        if (! $user) {
            return;
        }

        $message = match ($status) {
            'approaching' => 'Appointment ' . $appointment->id . ' SLA due soon.',
            'overdue' => 'Appointment ' . $appointment->id . ' SLA overdue.',
            default => null,
        };

        if (! $message) {
            return;
        }

        $link = '/appointments/' . $appointment->id;

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

