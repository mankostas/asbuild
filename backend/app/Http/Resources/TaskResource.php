<?php

namespace App\Http\Resources;

use App\Models\Team;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class TaskResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $data = parent::toArray($request);

        if ($this->assignee) {
            $kind = $this->assignee instanceof Team ? 'team' : 'employee';
            $data['assignee'] = [
                'id' => $this->assignee->id,
                'kind' => $kind,
                'label' => $this->assignee->name,
            ];
        } else {
            $data['assignee'] = null;
        }

        return $this->formatDates($data);
    }
}
