<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'target_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
