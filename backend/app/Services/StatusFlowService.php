<?php

namespace App\Services;

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
     * Get allowed transitions for a given status.
     *
     * @param string $status
     * @param array|null $map Optional transitions override
     * @return array
     */
    public function allowedTransitions(string $status, ?array $map = null): array
    {
        $map = $map ?? self::DEFAULT_TRANSITIONS;
        return $map[$status] ?? [];
    }

    /**
     * Determine if transition is allowed.
     */
    public function canTransition(string $from, string $to, ?array $map = null): bool
    {
        return in_array($to, $this->allowedTransitions($from, $map), true);
    }
}
