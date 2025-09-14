<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User as Employee;

class Team extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'lead_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'team_employee', 'team_id', 'employee_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'lead_id');
    }
}
