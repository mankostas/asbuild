<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class TenantOwnerResource extends JsonResource
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
            'last_login_at' => $this->last_login_at,
            'roles' => $this->roles->map(function ($role) {
                return [
                    'id' => $role->public_id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'tenant_id' => $role->tenant?->public_id,
                ];
            })->values(),
        ]);
    }
}
