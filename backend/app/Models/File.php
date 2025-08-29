<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class File extends Model
{
    protected $fillable = [
        'path',
        'filename',
        'mime_type',
        'size',
        'width',
        'height',
        'variants',
    ];

    protected $casts = [
        'variants' => 'array',
    ];

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_attachments')
            ->withPivot('field_key', 'section_key')
            ->withTimestamps();
    }
}
