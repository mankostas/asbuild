<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AppointmentComment extends Model
{
    protected $fillable = [
        'appointment_id',
        'user_id',
        'body',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'appointment_comment_files');
    }

    public function mentions(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'appointment_comment_mentions');
    }
}
