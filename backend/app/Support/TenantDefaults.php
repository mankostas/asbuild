<?php

namespace App\Support;

class TenantDefaults
{
    public const TASK_STATUSES = [
        ['slug' => 'draft', 'name' => 'Draft', 'color' => '#9ca3af'],
        ['slug' => 'assigned', 'name' => 'Assigned', 'color' => '#3b82f6'],
        ['slug' => 'in_progress', 'name' => 'In Progress', 'color' => '#f59e0b'],
        ['slug' => 'blocked', 'name' => 'Blocked', 'color' => '#ef4444'],
        ['slug' => 'review', 'name' => 'In Review', 'color' => '#8b5cf6'],
        ['slug' => 'completed', 'name' => 'Completed', 'color' => '#10b981'],
        ['slug' => 'rejected', 'name' => 'Rejected', 'color' => '#dc2626'],
        ['slug' => 'redo', 'name' => 'Redo', 'color' => '#f97316'],
    ];
}
