<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Services\FormSchemaService;
use App\Models\Tenant;

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
        'require_subtasks_complete',
        'abilities_json',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'statuses' => 'array',
        'status_flow_json' => 'array',
        'tenant_id' => 'integer',
        'require_subtasks_complete' => 'boolean',
        'abilities_json' => 'array',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function slaPolicies(): HasMany
    {
        return $this->hasMany(TaskSlaPolicy::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(TaskAutomation::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    protected function schemaJson(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => app(FormSchemaService::class)->normalizeSchema(
                is_array($value) ? $value : (json_decode($value ?: '[]', true) ?? [])
            ),
        );
    }
}
