<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\File;
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
}
