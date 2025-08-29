<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStatus extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'tenant_id',
        'position',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status_slug', 'slug');
    }
}
