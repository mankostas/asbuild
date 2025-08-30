<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskTypeVersion extends Model
{
    protected $fillable = [
        'task_type_id',
        'semver',
        'schema_json',
        'statuses',
        'status_flow_json',
        'created_by',
        'published_at',
        'deprecated_at',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'statuses' => 'array',
        'status_flow_json' => 'array',
        'published_at' => 'datetime',
        'deprecated_at' => 'datetime',
    ];

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
