<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'tenant_id',
        'name',
        'description',
        'slug',
        'abilities',
        'level',
    ];

    protected $casts = [
        'public_id' => 'string',
        'abilities' => 'array',
        'description' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $role): void {
            if (empty($role->slug)) {
                $role->slug = Str::snake($role->name);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps()->withPivot('tenant_id');
    }
}
