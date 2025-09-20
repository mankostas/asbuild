<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branding extends Model
{
    use HasFactory;
    use HasPublicId;

    protected $fillable = [
        'public_id',
        'tenant_id',
        'name',
        'color',
        'secondary_color',
        'color_dark',
        'secondary_color_dark',
        'logo',
        'logo_dark',
        'email_from',
        'footer_left',
        'footer_right',
    ];

    protected $casts = [
        'public_id' => 'string',
    ];
}
