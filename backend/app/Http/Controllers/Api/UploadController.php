<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\MergeChunks;
use App\Services\FileStorageService;
use App\Models\Task;
use App\Support\PublicIdResolver;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{
    public function __construct(private PublicIdResolver $publicIdResolver)
    {
    }

    public function chunk(Request $request)
    {
        $data = $request->validate([
            'upload_id' => 'required|string',
            'index' => 'required|integer',
            'total' => 'required|integer',
            'filename' => 'required|string',
            'chunk' => 'required|file|mimes:' . implode(',', config('security.allowed_upload_mimes')) . '|max:' . config('security.max_upload_size'),
        ]);

        $path = $request->file('chunk')->storeAs('chunks/' . $data['upload_id'], $data['index']);

        $exists = DB::table('upload_chunks')
            ->where('upload_id', $data['upload_id'])
            ->where('chunk_index', $data['index'])
            ->exists();

        DB::table('upload_chunks')->updateOrInsert(
            ['upload_id' => $data['upload_id'], 'chunk_index' => $data['index']],
            ['path' => $path, 'created_at' => now(), 'updated_at' => now()]
        );

        if (! $exists) {
            $uploaded = DB::table('upload_chunks')->where('upload_id', $data['upload_id'])->count();

            if ($uploaded === (int) $data['total']) {
                MergeChunks::dispatch($data['upload_id'], $data['filename'], $data['total']);
            }
        }

        return response()->json(['uploaded' => true]);
    }

    public function cleanup()
    {
        $threshold = now()->subDay();

        $old = DB::table('upload_chunks')->where('created_at', '<', $threshold)->get();

        foreach ($old as $chunk) {
            Storage::delete($chunk->path);
            $dir = dirname($chunk->path);
            if (empty(Storage::files($dir))) {
                Storage::deleteDirectory($dir);
            }
        }

        DB::table('upload_chunks')->where('created_at', '<', $threshold)->delete();

        return response()->json(['cleaned' => true]);
    }

    public function finalize(Request $request, string $uploadId, FileStorageService $storage)
    {
        $input = $request->all();

        if (array_key_exists('task_id', $input) && $input['task_id'] !== null) {
            $input['task_id'] = (string) $input['task_id'];
        }

        $data = validator($input, [
            'filename' => 'required|string',
            'task_id' => ['required', 'string'],
            'field_key' => 'required|string',
            'section_key' => 'required|string',
        ])->validate();

        $taskId = $this->publicIdResolver->resolve(Task::class, $data['task_id']);

        if ($taskId === null) {
            throw ValidationException::withMessages([
                'task_id' => __('The selected task is invalid.'),
            ]);
        }

        $tempPath = 'files/' . $data['filename'];

        if (! Storage::exists($tempPath)) {
            return response()->json(['message' => 'merge_pending'], 409);
        }

        $uploaded = new UploadedFile(
            Storage::path($tempPath),
            $data['filename'],
            null,
            null,
            true
        );

        $file = $storage->store($uploaded);

        $task = Task::findOrFail($taskId);
        $task->attachments()->attach($file->id, [
            'field_key' => $data['field_key'],
            'section_key' => $data['section_key'],
        ]);

        Storage::delete($tempPath);

        return response()->json([
            'file_id' => $file->public_id,
            'name' => $file->filename,
            'variants' => $file->variants ?? [],
        ]);
    }
}
