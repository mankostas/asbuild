<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentPhoto extends Model
{
    protected $fillable = [
        'appointment_id',
        'file_id',
        'type',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
