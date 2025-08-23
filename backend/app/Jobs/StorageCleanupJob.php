<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\Manual;
use App\Models\AppointmentPhoto;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StorageCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $usedFileIds = $this->usedFileIds();

        File::whereNotIn('id', $usedFileIds)->each(function (File $file) {
            $this->deleteFile($file);
        });

        Tenant::all()->each(function (Tenant $tenant) {
            $retention = (int) DB::table('tenant_settings')
                ->where('tenant_id', $tenant->id)
                ->where('key', 'file_retention_days')
                ->value('value') ?? 30;

            $cutoff = now()->subDays($retention);
            $tenantFileIds = $this->fileIdsForTenant($tenant->id);

            File::whereIn('id', $tenantFileIds)
                ->where('created_at', '<', $cutoff)
                ->each(function (File $file) {
                    $this->deleteFile($file);
                });
        });
    }

    protected function usedFileIds(): array
    {
        return Manual::pluck('file_id')
            ->merge(AppointmentPhoto::pluck('file_id'))
            ->merge(DB::table('appointment_comment_files')->pluck('file_id'))
            ->unique()
            ->all();
    }

    protected function fileIdsForTenant(int $tenantId): array
    {
        $manualIds = Manual::where('tenant_id', $tenantId)->pluck('file_id');

        $photoIds = AppointmentPhoto::whereHas('appointment', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->pluck('file_id');

        $commentIds = DB::table('appointment_comment_files')
            ->join('appointment_comments', 'appointment_comment_files.appointment_comment_id', '=', 'appointment_comments.id')
            ->join('appointments', 'appointment_comments.appointment_id', '=', 'appointments.id')
            ->where('appointments.tenant_id', $tenantId)
            ->pluck('appointment_comment_files.file_id');

        return $manualIds->merge($photoIds)->merge($commentIds)->unique()->all();
    }

    protected function deleteFile(File $file): void
    {
        Storage::disk('local')->delete($file->path);
        foreach ($file->variants ?? [] as $variant) {
            Storage::disk('local')->delete($variant);
        }
        $file->delete();
    }
}
