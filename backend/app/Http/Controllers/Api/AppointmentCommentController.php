<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentComment;
use Illuminate\Http\Request;

class AppointmentCommentController extends Controller
{
    public function index(Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        return response()->json($appointment->comments()->with('user')->get());
    }

    public function store(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'body' => 'required|string',
        ]);

        $comment = $appointment->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return response()->json($comment, 201);
    }

    public function destroy(AppointmentComment $comment)
    {
        $this->authorize('delete', $comment->appointment);
        $comment->delete();
        return response()->json(['message' => 'deleted']);
    }
}
