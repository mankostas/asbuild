<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;
use App\Models\Tenant;

class EmployeeResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $this->resource->loadMissing(['tenant', 'roles.tenant']);

        $pivotTenants = collect();

        $pivotTenantIds = $this->roles
            ->pluck('pivot.tenant_id')
            ->filter()
            ->unique()
            ->values();

        if ($pivotTenantIds->isNotEmpty()) {
            $pivotTenants = Tenant::query()
                ->whereIn('id', $pivotTenantIds)
                ->get()
                ->keyBy('id');
        }

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
            'roles' => $this->roles->map(function ($role) use ($pivotTenants) {
                $pivotTenantId = $role->pivot->tenant_id ?? null;
                $tenantPublicId = null;

                if ($pivotTenantId !== null) {
                    $tenantPublicId = optional($pivotTenants->get($pivotTenantId))->public_id;
                }

                if ($tenantPublicId === null) {
                    $tenantPublicId = $role->tenant?->public_id;
                }

                return [
                    'id' => $role->public_id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'level' => $role->level,
                    'abilities' => $role->abilities,
                    'tenant_id' => $tenantPublicId,
                ];
            })->values(),
            'avatar' => $this->avatar,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}
