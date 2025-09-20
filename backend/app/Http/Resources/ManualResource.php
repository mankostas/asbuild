<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;

class ManualResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $this->resource->loadMissing(['tenant', 'client', 'file']);

        return $this->formatDates([
            'id' => $this->public_id,
            'tenant_id' => $this->tenant?->public_id,
            'category' => $this->category,
            'tags' => $this->tags ?? [],
            'client_id' => $this->client?->public_id,
            'client' => $this->when($this->client, function () {
                return [
                    'id' => $this->client->public_id,
                    'name' => $this->client->name,
                ];
            }),
            'file' => $this->when($this->file, function () {
                return [
                    'id' => $this->file->public_id,
                    'filename' => $this->file->filename,
                    'mime_type' => $this->file->mime_type,
                    'size' => $this->file->size,
                    'variants' => $this->file->variants,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
    }
}
