<?php

namespace App\Support;

class TenantDefaults
{
    public const TASK_STATUSES = [
        ['slug' => 'draft', 'name' => 'Draft', 'color' => '#9ca3af'],
        ['slug' => 'assigned', 'name' => 'Assigned', 'color' => '#3b82f6'],
        ['slug' => 'in_progress', 'name' => 'In Progress', 'color' => '#f59e0b'],
        ['slug' => 'completed', 'name' => 'Completed', 'color' => '#10b981'],
    ];
}
