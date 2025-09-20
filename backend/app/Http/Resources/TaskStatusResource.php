<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;
use App\Models\TaskStatus;

class TaskStatusResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $this->resource->loadMissing('tenant');

        return $this->formatDates([
            'id' => $this->public_id,
            'name' => $this->name,
            'slug' => TaskStatus::stripPrefix($this->slug),
            'color' => $this->color,
            'position' => $this->position,
            'tenant_id' => $this->tenant?->public_id,
            'tasks_count' => $this->tasks_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}
