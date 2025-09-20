<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Concerns\FormatsDateTimes;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $this->resource->loadMissing('tenant');

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
            'email' => $this->email,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'status' => $this->status,
            'archived_at' => $this->archived_at,
            'deleted_at' => $this->deleted_at,
        ]);
    }
}
