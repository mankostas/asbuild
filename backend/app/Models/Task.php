<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'scheduled_at',
        'sla_start_at',
        'sla_end_at',
        'started_at',
        'completed_at',
        'kau_notes',
        'form_data',
        'task_type_id',
        'assignee_type',
        'assignee_id',
        'title',
        'description',
        'priority',
        'due_at',
        'estimate_minutes',
        'reporter_user_id',
        'status_slug',
        'board_position',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sla_start_at' => 'datetime',
        'sla_end_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'form_data' => 'array',
        'due_at' => 'datetime',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(TaskType::class, 'task_type_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'status_slug', 'slug');
    }

    public function assignee(): MorphTo
    {
        return $this->morphTo();
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TaskWatcher::class);
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class);
    }
}
