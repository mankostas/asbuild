<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Concerns\FormatsDateTimes;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        return $this->formatDates([
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'archived_at' => $this->archived_at,
            'deleted_at' => $this->deleted_at,
        ]);
    }
}
