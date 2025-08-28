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
    ];

    protected $casts = [
        'form_schema' => 'array',
        'fields_summary' => 'array',
        'statuses' => 'array',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
