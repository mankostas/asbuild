<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SlaCheckJob;
use App\Jobs\StorageCleanupJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(SlaCheckJob::class)->hourly();
Schedule::job(StorageCleanupJob::class)->daily();
