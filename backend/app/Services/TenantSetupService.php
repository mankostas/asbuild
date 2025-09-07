<?php

namespace App\Services;

use App\Models\TaskStatus;
use App\Models\Tenant;
use App\Support\TenantDefaults;

class TenantSetupService
{
    public function createDefaultTaskStatuses(Tenant $tenant): void
    {
        foreach (TenantDefaults::TASK_STATUSES as $index => $status) {
            TaskStatus::create([
                'tenant_id' => $tenant->id,
                'slug' => $status['slug'],
                'name' => $status['name'],
                'color' => $status['color'],
                'position' => $index + 1,
            ]);
        }
    }
}
