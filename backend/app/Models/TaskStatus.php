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

    public static function prefixSlug(string $slug, ?int $tenantId): string
    {
        return $tenantId ? 't' . $tenantId . '__' . $slug : $slug;
    }

    public static function stripPrefix(string $slug): string
    {
        return (string) preg_replace('/^t\d+__/', '', $slug);
    }

    protected static function booted(): void
    {
        static::saving(function (self $status): void {
            if (empty($status->slug)) {
                $status->slug = Str::snake($status->name);
            }
            $status->slug = self::prefixSlug(self::stripPrefix($status->slug), $status->tenant_id);
        });
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status_slug', 'slug');
    }
}
