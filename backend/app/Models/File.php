<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
