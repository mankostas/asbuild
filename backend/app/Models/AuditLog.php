<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
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

    protected $table = 'audit_logs';
}
