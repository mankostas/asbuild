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
        $this->comment->loadMissing('task');

        $mail = (new MailMessage)
            ->line('You were mentioned in a task comment.');

        $taskPublicId = $this->comment->task?->public_id;

        if ($taskPublicId) {
            $mail->action('View Task', url('/tasks/' . $taskPublicId));
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        $this->comment->loadMissing('task');

        return [
            'task_public_id' => $this->comment->task?->public_id,
            'comment_public_id' => $this->comment->public_id,
        ];
    }
}
