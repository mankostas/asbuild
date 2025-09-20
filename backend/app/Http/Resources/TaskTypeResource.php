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
        $this->resource->loadMissing(['tenant', 'client']);

        $data = parent::toArray($request);
        $data['id'] = $this->public_id;
        $data['tenant_id'] = $this->tenant?->public_id;
        $data['client_id'] = $this->client?->public_id;
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
                    'id' => $this->client->public_id,
                    'name' => $this->client->name,
                ]
                : null;
        }
        if ($this->relationLoaded('tenant') || $this->tenant) {
            $data['tenant'] = $this->tenant
                ? [
                    'id' => $this->tenant->public_id,
                    'name' => $this->tenant->name,
                ]
                : null;
        }
        return $this->formatDates($data);
    }
}
