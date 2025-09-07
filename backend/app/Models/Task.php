<?php

namespace App\Models;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\App;
use App\Models\TaskSlaEvent;
use App\Models\TaskAutomation;

class Task extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

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
        'assigned_user_id',
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TaskWatcher::class);
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class);
    }

    public function attachments(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'task_attachments')
            ->withPivot('field_key', 'section_key')
            ->withTimestamps();
    }

    public function attachmentsByField(string $fieldKey): BelongsToMany
    {
        return $this->attachments()->wherePivot('field_key', $fieldKey);
    }

    public function attachmentsBySection(string $sectionKey): BelongsToMany
    {
        return $this->attachments()->wherePivot('section_key', $sectionKey);
    }

    public function slaEvents(): HasMany
    {
        return $this->hasMany(TaskSlaEvent::class);
    }

    protected static function booted(): void
    {
        static::updated(function (Task $task) {
            if ($task->wasChanged('status_slug')) {
                TaskAutomation::run($task, 'status_changed');
            }
        });
    }
}
