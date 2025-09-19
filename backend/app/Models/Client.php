<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'notes',
        'status',
        'archived_at',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'archived_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function taskTypes(): HasMany
    {
        return $this->hasMany(TaskType::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
