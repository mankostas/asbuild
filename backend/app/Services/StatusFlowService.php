<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskType;

class StatusFlowService
{
    /**
     * Default allowed transitions between statuses.
     *
     * @var array<string, array<int, string>>
     */
    public const DEFAULT_TRANSITIONS = [
        'draft' => ['assigned', 'blocked'],
        'assigned' => ['in_progress', 'blocked'],
        'in_progress' => ['review', 'blocked'],
        'blocked' => ['assigned'],
        'review' => ['completed', 'redo', 'rejected'],
        'redo' => ['in_progress'],
        'rejected' => [],
        'completed' => [],
    ];

    /**
     * Build transition map for given task type.
     */
    public function transitions(TaskType|null $type = null): array
    {
        $map = $type?->status_flow_json;
        if (is_array($map) && count($map) > 0) {
            if (array_is_list($map)) {
                $graph = [];
                foreach ($map as $edge) {
                    if (is_array($edge) && count($edge) === 2) {
                        [$from, $to] = $edge;

                        $from = is_array($from) ? ($from['slug'] ?? null) : $from;
                        $to = is_array($to) ? ($to['slug'] ?? null) : $to;

                        if (is_scalar($from) && is_scalar($to)) {
                            $graph[(string) $from][] = (string) $to;
                        }
                    }
                }

                return $graph;
            }

            return $map;
        }

        return self::DEFAULT_TRANSITIONS;
    }

    /**
     * Get allowed transitions for a given status.
     */
    public function allowedTransitions(string $status, TaskType|null $type = null): array
    {
        $map = $this->transitions($type);

        return $map[$status] ?? [];
    }

    /**
     * Determine terminal statuses with no outgoing transitions.
     */
    public function terminalStatuses(TaskType|null $type = null): array
    {
        $map = $this->transitions($type);

        $all = array_keys($map);
        foreach ($map as $edges) {
            foreach ($edges as $to) {
                if (! in_array($to, $all, true)) {
                    $all[] = $to;
                }
            }
        }

        return array_values(array_filter($all, fn ($status) => empty($map[$status] ?? [])));
    }

    /**
     * Determine if transition is allowed.
     */
    public function canTransition(string $from, string $to, TaskType|null $type = null): bool
    {
        return in_array($to, $this->allowedTransitions($from, $type), true);
    }

    /**
     * Validate transition constraints and abort with 422 on failure.
     */
    public function checkConstraints(Task $task, string $toSlug): void
    {
        $type = $task->type;
        if (! $type) {
            return;
        }

        $statuses = collect($type->statuses ?? []);
        if ($statuses->isEmpty()) {
            return;
        }

        $initial = data_get($statuses->first(), 'slug');
        $terminals = $this->terminalStatuses($type);

        if ($toSlug !== $initial && empty($task->assigned_user_id)) {
            $this->abort422(
                'assignee_required',
                __('Assignee is required to leave the initial status.')
            );
        }

        if (in_array($toSlug, $terminals, true) && $this->hasIncompleteRequiredSubtasks($task)) {
            $this->abort422(
                'subtasks_incomplete',
                __('Complete required subtasks before finishing.')
            );
        }

        if (in_array($toSlug, $terminals, true) && ! $this->hasAllRequiredPhotos($task, $type->schema_json)) {
            $this->abort422(
                'photos_required',
                __('Required photos are missing.')
            );
        }
    }

    protected function abort422(string $code, string $message): void
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json(['message' => $message, 'code' => $code], 422)
        );
    }

    protected function hasIncompleteRequiredSubtasks(Task $task): bool
    {
        return $task->subtasks()
            ->where('is_required', true)
            ->where(function ($q) {
                $q->where('is_completed', false)->orWhereNull('is_completed');
            })
            ->exists();
    }

    protected function hasAllRequiredPhotos(Task $task, $schemaJson): bool
    {
        $schema = is_array($schemaJson)
            ? $schemaJson
            : (json_decode((string) $schemaJson, true) ?? []);

        $required = collect(data_get($schema, 'sections', []))
            ->flatMap(fn ($s) => data_get($s, 'photos', []))
            ->filter(fn ($p) => data_get($p, 'required') === true)
            ->pluck('key')
            ->filter()
            ->unique();

        if ($required->isEmpty()) {
            return true;
        }

        $attached = $task->attachments()
            ->whereIn('task_attachments.field_key', $required)
            ->pluck('task_attachments.field_key')
            ->unique();

        return $attached->count() === $required->count();
    }
}
