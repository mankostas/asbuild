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
        'draft' => ['assigned'],
        'assigned' => ['in_progress'],
        'in_progress' => ['completed'],
        'completed' => ['rejected', 'redo'],
        'rejected' => ['assigned'],
        'redo' => ['assigned'],
    ];

    /**
     * Build transition map for given task type.
     */
    public function transitions(?TaskType $type = null): array
    {
        $map = $type?->status_flow_json;
        if (is_array($map)) {
            if (array_is_list($map)) {
                $graph = [];
                foreach ($map as $edge) {
                    if (is_array($edge) && count($edge) === 2) {
                        [$from, $to] = $edge;
                        $graph[$from][] = $to;
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
    public function allowedTransitions(string $status, ?TaskType $type = null): array
    {
        $map = $this->transitions($type);
        return $map[$status] ?? [];
    }

    /**
     * Determine if transition is allowed.
     */
    public function canTransition(string $from, string $to, ?TaskType $type = null): bool
    {
        return in_array($to, $this->allowedTransitions($from, $type), true);
    }

    /**
     * Validate constraints for transition. Returns reason string on failure or null on success.
     */
    public function checkConstraints(Task $task, string $next): ?string
    {
        $type = $task->type;
        if (! $type) {
            return null;
        }

        if ($next !== 'completed') {
            return null;
        }

        $form = $task->form_data ?? [];
        $fields = collect($type->schema_json['sections'] ?? [])
            ->flatMap(fn ($s) => $s['fields'] ?? []);

        foreach ($fields as $field) {
            if (! ($field['required'] ?? false)) {
                continue;
            }
            $key = $field['key'];
            if (! array_key_exists($key, $form)) {
                return $this->isPhotoField($field) ? 'missing_photo' : 'missing_field';
            }
            $value = $form[$key];
            if ($this->isPhotoField($field)) {
                if (empty($value) || (is_array($value) && count($value) === 0)) {
                    return 'missing_photo';
                }
            } else {
                if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                    return 'missing_field';
                }
            }
        }

        if ($type->require_subtasks_complete &&
            $task->subtasks()->where('is_required', true)->where('is_completed', false)->exists()) {
            return 'subtasks_incomplete';
        }

        return null;
    }

    protected function isPhotoField(array $field): bool
    {
        $type = $field['type'] ?? '';
        return str_contains((string) $type, 'photo');
    }
}
