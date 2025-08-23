<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Manual extends Model
{
    protected $fillable = [
        'tenant_id',
        'file_id',
        'category',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
