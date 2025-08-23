<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'granted_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
    ];
}
