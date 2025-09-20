<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasPublicId;

    protected $fillable = ['public_id', 'name', 'tenant_id'];

    protected $casts = [
        'public_id' => 'string',
    ];
}

