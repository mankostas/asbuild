<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class FileStorageService
{
    public function store(UploadedFile $uploaded): File
    {
        $uuid = (string) Str::uuid();
        $extension = strtolower($uploaded->getClientOriginalExtension());
        $dir = 'files/' . date('Y/m/d');
        $filename = $uuid . '.' . $extension;
        $path = $uploaded->storeAs($dir, $filename);

        $variants = $this->createVariants($uploaded->getRealPath(), $uuid, $dir, $width, $height);

        return File::create([
            'path' => $path,
            'filename' => $uploaded->getClientOriginalName(),
            'mime_type' => $uploaded->getClientMimeType(),
            'size' => $uploaded->getSize(),
            'width' => $width,
            'height' => $height,
            'variants' => $variants,
        ]);
    }

    protected function createVariants(string $source, string $uuid, string $dir, ?int &$width = null, ?int &$height = null): array
    {
        $variants = [];

        if (extension_loaded('imagick')) {
            $image = new \Imagick($source);
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            $orientation = $image->getImageOrientation();
            $image->stripImage();
            $image->setImageOrientation($orientation);

            $image->setImageFormat('webp');
            $displayPath = Storage::disk('local')->path($dir . '/' . $uuid . '_display.webp');
            $image->writeImage($displayPath);
            $variants['display'] = $dir . '/' . $uuid . '_display.webp';

            $thumb = clone $image;
            $thumb->thumbnailImage(300, 0);
            $thumbPath = Storage::disk('local')->path($dir . '/' . $uuid . '_thumb.webp');
            $thumb->writeImage($thumbPath);
            $thumb->destroy();
            $variants['thumb'] = $dir . '/' . $uuid . '_thumb.webp';

            $image->destroy();
        } else {
            $data = @file_get_contents($source);
            $img = $data ? imagecreatefromstring($data) : false;
            if ($img) {
                $width = imagesx($img);
                $height = imagesy($img);
                $displayPath = Storage::disk('local')->path($dir . '/' . $uuid . '_display.webp');
                imagewebp($img, $displayPath);
                $variants['display'] = $dir . '/' . $uuid . '_display.webp';

                $newWidth = 300;
                $newHeight = intval($height * ($newWidth / $width));
                $thumbImg = imagescale($img, $newWidth, $newHeight);
                $thumbPath = Storage::disk('local')->path($dir . '/' . $uuid . '_thumb.webp');
                imagewebp($thumbImg, $thumbPath);
                imagedestroy($thumbImg);
                imagedestroy($img);
                $variants['thumb'] = $dir . '/' . $uuid . '_thumb.webp';
            }
        }

        return $variants;
    }

    public function getSignedUrl(File $file, string $variant = 'original', int $ttl = 300): string
    {
        return URL::temporarySignedRoute('files.download', now()->addSeconds($ttl), [
            'file' => $file->id,
            'variant' => $variant,
        ]);
    }

    public function stream(File $file, string $variant = 'original')
    {
        $path = $variant === 'original' ? $file->path : ($file->variants[$variant] ?? null);
        if (! $path) {
            abort(404);
        }

        $fullPath = Storage::disk('local')->path($path);
        $mime = $variant === 'original' ? $file->mime_type : 'image/webp';

        if (! file_exists($fullPath)) {
            abort(404);
        }

        if (ini_get('xsendfile.force') || env('X_SENDFILE')) {
            return response()->noContent()->withHeaders([
                'X-Sendfile' => $fullPath,
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
            ]);
        }

        return response()->stream(function () use ($fullPath) {
            $stream = fopen($fullPath, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"',
        ]);
    }
}
