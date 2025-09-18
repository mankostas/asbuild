<?php

namespace App\Http\Resources;

use App\Services\FormSchemaService;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class TaskTypeResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $data = parent::toArray($request);
        if (isset($data['schema_json'])) {
            $service = app(FormSchemaService::class);
            $data['schema_json'] = $service->filterSchemaForRoles(
                $data['schema_json'],
                $request->user()
            );
        }
        if ($this->relationLoaded('client') || $this->client) {
            $data['client'] = $this->client
                ? [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                ]
                : null;
        }
        return $this->formatDates($data);
    }
}
