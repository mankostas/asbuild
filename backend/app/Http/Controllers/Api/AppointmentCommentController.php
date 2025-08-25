<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\Notifier;

class AppointmentCommentController extends Controller
{
    public function index(Appointment $appointment)
    {
        $this->authorize('view', $appointment);
        return response()->json(
            $appointment->comments()->with(['user', 'files', 'mentions'])->get()
        );
    }

    public function store(Request $request, Appointment $appointment)
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'body' => 'required|string',
            'files' => 'array',
            'files.*' => 'integer|exists:files,id',
        ]);

        $comment = $appointment->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        if (!empty($data['files'])) {
            $comment->files()->attach($data['files']);
        }

        preg_match_all('/@([\w]+)/', $data['body'], $matches);
        $names = array_unique($matches[1] ?? []);
        if ($names) {
            $mentioned = User::whereIn('name', $names)->get();
            $allowed = $mentioned->filter(fn ($user) => Gate::forUser($user)->allows('view', $appointment));
            if ($allowed->isNotEmpty()) {
                $comment->mentions()->attach($allowed->pluck('id'));
                $allowed->each(function ($user) use ($appointment) {
                    app(Notifier::class)->send(
                        $user,
                        'comment',
                        'You were mentioned in an appointment comment.',
                        '/appointments/' . $appointment->id
                    );
                });
            }
        }

        return response()->json($comment->load(['user', 'files', 'mentions']), 201);
    }

    public function show(AppointmentComment $comment)
    {
        $this->authorize('view', $comment->appointment);
        return response()->json($comment->load(['user', 'files', 'mentions']));
    }

    public function update(Request $request, AppointmentComment $comment)
    {
        $this->authorize('update', $comment->appointment);

        $data = $request->validate([
            'body' => 'required|string',
            'files' => 'array',
            'files.*' => 'integer|exists:files,id',
        ]);

        $comment->body = $data['body'];
        $comment->save();

        if (array_key_exists('files', $data)) {
            $comment->files()->sync($data['files']);
        }

        preg_match_all('/@([\w]+)/', $data['body'], $matches);
        $names = array_unique($matches[1] ?? []);
        $comment->mentions()->sync([]);
        if ($names) {
            $mentioned = User::whereIn('name', $names)->get();
            $allowed = $mentioned->filter(fn ($user) => Gate::forUser($user)->allows('view', $comment->appointment));
            if ($allowed->isNotEmpty()) {
                $comment->mentions()->sync($allowed->pluck('id'));
                $allowed->each(function ($user) use ($comment) {
                    app(Notifier::class)->send(
                        $user,
                        'comment',
                        'You were mentioned in an appointment comment.',
                        '/appointments/' . $comment->appointment_id
                    );
                });
            }
        }

        return response()->json($comment->load(['user', 'files', 'mentions']));
    }

    public function destroy(AppointmentComment $comment)
    {
        $this->authorize('delete', $comment->appointment);
        $comment->delete();
        return response()->json(['message' => 'deleted']);
    }
}
