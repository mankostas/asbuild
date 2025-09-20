<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use App\Services\FormSchemaService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property array|null $schema_json Schema definition
 */
class TaskType extends Model
{
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'name',
        'schema_json',
        'statuses',
        'status_flow_json',
        'tenant_id',
        'client_id',
        'require_subtasks_complete',
        'abilities_json',
    ];

    protected $casts = [
        'public_id' => 'string',
        'schema_json' => 'array',
        'statuses' => 'array',
        'status_flow_json' => 'array',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
