<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class RoleResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $this->resource->loadMissing('tenant');

        return $this->formatDates([
            'id' => $this->public_id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'abilities' => $this->abilities,
            'tenant_id' => $this->tenant?->public_id,
            'level' => $this->level,
            'users_count' => $this->users_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}
