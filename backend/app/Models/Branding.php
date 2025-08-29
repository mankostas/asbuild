<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branding extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'color',
        'secondary_color',
        'logo',
        'logo_dark',
        'email_from',
        'footer_left',
        'footer_right',
    ];
}
