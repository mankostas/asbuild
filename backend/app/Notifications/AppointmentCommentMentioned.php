<?php

namespace App\Notifications;

use App\Models\AppointmentComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCommentMentioned extends Notification
{
    use Queueable;

    public function __construct(public AppointmentComment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('You were mentioned in an appointment comment.')
            ->action('View Appointment', url('/appointments/' . $this->comment->appointment_id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->comment->appointment_id,
            'comment_id' => $this->comment->id,
        ];
    }
}
