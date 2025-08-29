<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array|null $schema_json Schema definition
 */
class TaskType extends Model
{
    protected $fillable = [
        'name',
        'schema_json',
        'statuses',
        'status_flow_json',
        'tenant_id',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'statuses' => 'array',
        'status_flow_json' => 'array',
        'tenant_id' => 'integer',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
