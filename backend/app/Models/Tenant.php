<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'quota_storage_mb',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
    ];
}
