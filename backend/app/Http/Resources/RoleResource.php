<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class RoleResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        return $this->formatDates([
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'abilities' => $this->abilities,
            'tenant_id' => $this->tenant_id,
            'level' => $this->level,
        ]);
    }
}
