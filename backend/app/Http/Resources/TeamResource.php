<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class TeamResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $this->resource->loadMissing(['tenant', 'lead', 'employees']);

        return $this->formatDates([
            'id' => $this->public_id,
            'tenant_id' => $this->tenant?->public_id,
            'tenant' => $this->when($this->tenant, function () {
                return [
                    'id' => $this->tenant->public_id,
                    'name' => $this->tenant->name,
                ];
            }),
            'name' => $this->name,
            'description' => $this->description,
            'lead_id' => $this->lead?->public_id,
            'lead' => $this->when($this->lead, function () {
                return [
                    'id' => $this->lead->public_id,
                    'name' => $this->lead->name,
                    'email' => $this->lead->email,
                ];
            }),
            'employees' => $this->employees->map(function ($employee) {
                return [
                    'id' => $employee->public_id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                ];
            })->values(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}
