<?php

namespace App\Http\Resources;

use App\Services\FormSchemaService;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Concerns\FormatsDateTimes;
use App\Models\TaskStatus;

class TaskResource extends JsonResource
{
    use FormatsDateTimes;

    public function toArray($request): array
    {
        $data = parent::toArray($request);
        $data['status_slug'] = TaskStatus::stripPrefix($data['status_slug'] ?? '');
        $data['previous_status_slug'] = isset($data['previous_status_slug'])
            ? TaskStatus::stripPrefix($data['previous_status_slug'])
            : null;

        if ($this->assignee) {
            $data['assignee'] = [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ];
        } else {
            $data['assignee'] = null;
        }

        $data['status_color'] = $this->status->color ?? null;

        if ($this->relationLoaded('client') || $this->client) {
            $data['client'] = $this->client
                ? [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                ]
                : null;
        }

        $data['counts'] = [
            'comments' => $this->comments_count ?? 0,
            'attachments' => $this->attachments_count ?? 0,
            'watchers' => $this->watchers_count ?? 0,
            'subtasks' => $this->subtasks_count ?? 0,
        ];

        $data['sla_chip'] = null;
        if ($this->sla_end_at) {
            $data['sla_chip'] = now()->greaterThan($this->sla_end_at) ? 'breached' : 'ok';
        }

        $data['is_watching'] = $this->relationLoaded('watchers')
            ? $this->watchers->contains('user_id', $request->user()->id)
            : false;

        if ($this->type && $this->type->schema_json) {
            $service = app(FormSchemaService::class);
            $data['form_data'] = $service->filterDataForRoles(
                $this->type->schema_json,
                $data['form_data'] ?? [],
                $request->user()
            );
        }

        return $this->formatDates($data);
    }
}
