<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array|null $schema_json Schema definition
 */
class TaskType extends Model
{
    protected $fillable = [
        'name',
        'schema_json',
        'statuses',
        'status_flow_json',
        'tenant_id',
        'require_subtasks_complete',
        'current_version_id',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'statuses' => 'array',
        'status_flow_json' => 'array',
        'tenant_id' => 'integer',
        'require_subtasks_complete' => 'boolean',
        'current_version_id' => 'integer',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TaskTypeVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(TaskTypeVersion::class, 'current_version_id');
    }

    public function slaPolicies(): HasMany
    {
        return $this->hasMany(TaskSlaPolicy::class);
    }
}
