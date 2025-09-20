<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TaskStatus extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'slug',
        'name',
        'tenant_id',
        'position',
        'color',
    ];

    protected $casts = [
        'public_id' => 'string',
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

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
