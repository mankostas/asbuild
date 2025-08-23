<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Facades\Mail;

class Notifier
{
    public function send(User $user, string $category, string $message, ?string $link = null): void
    {
        $pref = UserNotificationPreference::for($user, $category);

        if ($pref->inapp) {
            Notification::create([
                'user_id' => $user->id,
                'category' => $category,
                'message' => $message,
                'link' => $link,
            ]);
        }

        if ($pref->email) {
            Mail::raw($message . ($link ? "\n\n" . $link : ''), function ($mail) use ($user) {
                $mail->to($user->email)->subject('Notification');
            });
        }

        // SMS channel can be added here in the future
    }
}
