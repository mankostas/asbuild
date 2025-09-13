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
        $data = parent::toArray($request);
        $data['slug'] = TaskStatus::stripPrefix($data['slug']);

        return $this->formatDates($data);
    }
}
