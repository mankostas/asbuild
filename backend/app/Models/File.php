<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class File extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'path',
        'filename',
        'mime_type',
        'size',
        'width',
        'height',
        'variants',
    ];

    protected $casts = [
        'public_id' => 'string',
        'variants' => 'array',
    ];

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_attachments')
            ->withPivot('field_key', 'section_key')
            ->withTimestamps();
    }
}
