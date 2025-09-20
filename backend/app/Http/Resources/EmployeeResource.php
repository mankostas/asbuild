<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class EmployeeResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $this->resource->loadMissing(['tenant', 'roles.tenant']);

        return $this->formatDates([
            'id' => $this->public_id,
            'tenant_id' => $this->tenant?->public_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'department' => $this->department,
            'status' => $this->status,
            'last_login_at' => $this->last_login_at,
            'roles' => $this->roles->map(function ($role) {
                return [
                    'id' => $role->public_id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'level' => $role->level,
                    'abilities' => $role->abilities,
                    'tenant_id' => $role->tenant?->public_id,
                ];
            })->values(),
            'avatar' => $this->avatar,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}
