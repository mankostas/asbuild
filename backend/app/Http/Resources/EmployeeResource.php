<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class EmployeeResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $data = parent::toArray($request);
        $data['status'] = $this->status;
        $data['last_login_at'] = $this->last_login_at;

        return $this->formatDates($data);
    }
}
