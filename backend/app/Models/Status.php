<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $fillable = ['name', 'tenant_id'];

    protected $casts = [
        'tenant_id' => 'integer',
    ];
}

