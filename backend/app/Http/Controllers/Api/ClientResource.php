<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Concerns\FormatsDateTimes;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $owner = null;
        if ($this->relationLoaded('owner') || $this->owner) {
            $owner = $this->owner
                ? [
                    'id' => $this->owner->id,
                    'name' => $this->owner->name,
                ]
                : null;
        }

        return $this->formatDates([
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'archived_at' => $this->archived_at,
            'deleted_at' => $this->deleted_at,
            'owner' => $owner,
        ]);
    }
}
