<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubtask extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'task_id',
        'title',
        'is_completed',
        'assigned_user_id',
        'is_required',
        'position',
    ];

    protected $casts = [
        'public_id' => 'string',
        'is_completed' => 'boolean',
        'is_required' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
