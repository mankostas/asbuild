<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Appointment;
use App\Services\FileStorageService;
use Illuminate\Http\Request;

class FileController extends Controller
{
    public function download(Request $request, File $file, FileStorageService $storage, string $variant = 'original')
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        return $storage->stream($file, $variant ?? 'original');
    }

    public function attachToAppointment(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'file_id' => 'required|exists:files,id',
        ]);

        $attachment = $appointment->photos()->create([
            'file_id' => $data['file_id'],
            'type' => 'attachment',
        ]);

        return response()->json($attachment->load('file'), 201);
    }
}
