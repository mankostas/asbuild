<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'tenant_id',
        'name',
        'email',
        'phone',
        'notes',
        'status',
        'archived_at',
    ];

    protected $casts = [
        'public_id' => 'string',
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
