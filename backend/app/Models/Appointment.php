<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;
use App\Services\StatusFlowService;

class Appointment extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_REDO = 'redo';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'scheduled_at',
        'sla_start_at',
        'sla_end_at',
        'started_at',
        'completed_at',
        'kau_notes',
        'form_data',
        'appointment_type_id',
        'assignee_type',
        'assignee_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sla_start_at' => 'datetime',
        'sla_end_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'form_data' => 'array',
    ];

    protected $appends = [
        'form_schema',
    ];

    protected static $transitions = StatusFlowService::DEFAULT_TRANSITIONS;

    public function photos(): HasMany
    {
        return $this->hasMany(AppointmentPhoto::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AppointmentComment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class, 'appointment_type_id');
    }

    public function assignee(): MorphTo
    {
        return $this->morphTo();
    }

    public function getFormSchemaAttribute()
    {
        return $this->type->form_schema ?? null;
    }

    public function canTransitionTo(string $status): bool
    {
        $transitions = $this->type->statuses ?? self::$transitions;
        return in_array($status, $transitions[$this->status] ?? []);
    }

    public function getSlaStatusAttribute(): string
    {
        if (! $this->sla_end_at) {
            return 'none';
        }

        $reference = $this->completed_at ?? Carbon::now();

        return $reference->lte($this->sla_end_at) ? 'within' : 'breached';
    }
}
