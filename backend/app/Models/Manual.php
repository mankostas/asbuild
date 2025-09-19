<?php

namespace App\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Manual extends Model
{
    protected $fillable = [
        'tenant_id',
        'file_id',
        'category',
        'tags',
        'client_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'client_id' => 'integer',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
