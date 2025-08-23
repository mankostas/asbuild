<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UserNotificationPreference;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            Notification::where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        abort_if($notification->user_id !== $request->user()->id, 403);
        $notification->update(['read_at' => now()]);
        return response()->json(['status' => 'ok']);
    }

    public function getPreferences(Request $request)
    {
        return response()->json(
            UserNotificationPreference::forUser($request->user())
        );
    }

    public function updatePreferences(Request $request)
    {
        $data = $request->validate([
            '*.category' => 'required|string',
            '*.inapp' => 'boolean',
            '*.email' => 'boolean',
            '*.sms' => 'boolean',
        ]);

        foreach ($data as $pref) {
            UserNotificationPreference::updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'category' => $pref['category'],
                ],
                [
                    'inapp' => $pref['inapp'] ?? false,
                    'email' => $pref['email'] ?? false,
                    'sms' => $pref['sms'] ?? false,
                ]
            );
        }

        return response()->json(['status' => 'ok']);
    }
}
