<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentType extends Model
{
    protected $fillable = [
        'name',
        'form_schema',
        'fields_summary',
        'statuses',
        'tenant_id',
    ];

    protected $casts = [
        'form_schema' => 'array',
        'fields_summary' => 'array',
        'statuses' => 'array',
        'tenant_id' => 'integer',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
