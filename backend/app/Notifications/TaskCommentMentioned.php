<?php

namespace App\Notifications;

use App\Models\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskCommentMentioned extends Notification
{
    use Queueable;

    public function __construct(public TaskComment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('You were mentioned in a task comment.')
            ->action('View Task', url('/tasks/' . $this->comment->task_id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->comment->task_id,
            'comment_id' => $this->comment->id,
        ];
    }
}
