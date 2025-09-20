<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class Consent extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'user_id',
        'name',
        'granted_at',
    ];

    protected $casts = [
        'public_id' => 'string',
        'granted_at' => 'datetime',
    ];
}
