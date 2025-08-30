<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TaskStatus extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'tenant_id',
        'position',
        'color',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $status): void {
            if (empty($status->slug)) {
                $status->slug = Str::snake($status->name);
            }
        });
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status_slug', 'slug');
    }
}
