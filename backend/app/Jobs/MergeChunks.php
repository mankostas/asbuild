<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MergeChunks implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $uploadId,
        public string $filename,
        public int $total
    ) {
    }

    public function handle(): void
    {
        $finalPath = 'files/' . $this->filename;
        Storage::makeDirectory('files');
        $fullPath = Storage::path($finalPath);

        $output = fopen($fullPath, 'wb');

        $chunks = DB::table('upload_chunks')
            ->where('upload_id', $this->uploadId)
            ->orderBy('chunk_index')
            ->get();

        foreach ($chunks as $chunk) {
            $input = fopen(Storage::path($chunk->path), 'rb');
            stream_copy_to_stream($input, $output);
            fclose($input);
        }

        fclose($output);

        foreach ($chunks as $chunk) {
            Storage::delete($chunk->path);
        }

        Storage::deleteDirectory('chunks/' . $this->uploadId);
        DB::table('upload_chunks')->where('upload_id', $this->uploadId)->delete();
    }
}
