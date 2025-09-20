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
        $this->resource->loadMissing([
            'tenant',
            'type.tenant',
            'type.client',
            'client',
            'assignee',
            'user',
            'reporter',
        ]);

        $data = parent::toArray($request);
        $data['id'] = $this->public_id;
        $data['tenant_id'] = $this->tenant?->public_id;
        $data['user_id'] = $this->user?->public_id;
        $data['reporter_user_id'] = $this->reporter?->public_id;
        $data['task_type_id'] = $this->type?->public_id;
        $data['client_id'] = $this->client?->public_id;
        $data['assigned_user_id'] = $this->assignee?->public_id;
        $data['status_slug'] = TaskStatus::stripPrefix($data['status_slug'] ?? '');
        $data['previous_status_slug'] = isset($data['previous_status_slug'])
            ? TaskStatus::stripPrefix($data['previous_status_slug'])
            : null;

        if ($this->relationLoaded('type') && $this->type) {
            $data['type'] = (new TaskTypeResource($this->type))->toArray($request);
        }

        if ($this->assignee) {
            $data['assignee'] = [
                'id' => $this->assignee->public_id,
                'name' => $this->assignee->name,
            ];
        } else {
            $data['assignee'] = null;
        }

        if ($this->relationLoaded('user') && $this->user) {
            $data['user'] = [
                'id' => $this->user->public_id,
                'name' => $this->user->name,
            ];
        }

        if ($this->relationLoaded('reporter') && $this->reporter) {
            $data['reporter'] = [
                'id' => $this->reporter->public_id,
                'name' => $this->reporter->name,
            ];
        }

        $data['status_color'] = $this->status->color ?? null;

        if ($this->relationLoaded('client') || $this->client) {
            $data['client'] = $this->client
                ? [
                    'id' => $this->client->public_id,
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

        if ($this->relationLoaded('watchers')) {
            $this->watchers->loadMissing('user');
            $data['watchers'] = $this->watchers->map(function ($watcher) {
                return [
                    'id' => $watcher->public_id,
                    'user_id' => $watcher->user?->public_id,
                    'user' => $watcher->user
                        ? [
                            'id' => $watcher->user->public_id,
                            'name' => $watcher->user->name,
                        ]
                        : null,
                ];
            })->values();
        }

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
