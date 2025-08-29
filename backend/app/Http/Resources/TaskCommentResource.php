<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class TaskCommentResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        return $this->formatDates([
            'id' => $this->id,
            'body' => $this->body,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'mentions' => $this->mentions->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values(),
            'created_at' => $this->created_at,
        ]);
    }
}
