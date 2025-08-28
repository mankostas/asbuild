<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class RoleResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        return $this->formatDates(parent::toArray($request));
    }
}
