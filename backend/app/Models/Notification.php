<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'user_id',
        'category',
        'message',
        'link',
        'read_at',
    ];

    protected $casts = [
        'public_id' => 'string',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
