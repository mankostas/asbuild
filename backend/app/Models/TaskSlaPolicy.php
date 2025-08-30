<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSlaPolicy extends Model
{
    protected $fillable = [
        'task_type_id',
        'priority',
        'response_within_mins',
        'resolve_within_mins',
        'calendar_json',
    ];

    protected $casts = [
        'calendar_json' => 'array',
    ];

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }
}
