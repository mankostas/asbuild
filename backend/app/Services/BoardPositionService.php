<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskStatus;

/**
 * Manage board positions for tasks using a gap strategy.
 */
class BoardPositionService
{
    /**
     * Move a task to a new status and board index.
     */
    public function move(Task $task, string $statusSlug, int $index): void
    {
        $tenantId = $task->tenant_id;
        $previous = $task->status_slug;

        // Get ordered tasks in target column
        $siblings = Task::where('tenant_id', $tenantId)
            ->where('status_slug', $statusSlug)
            ->where('id', '!=', $task->id)
            ->orderBy('board_position')
            ->get(['id', 'board_position']);

        $prev = $siblings[$index - 1] ?? null;
        $next = $siblings[$index] ?? null;

        $position = $this->calculatePosition($prev->board_position ?? null, $next->board_position ?? null);

        if ($position === null) {
            $this->resequence($tenantId, $statusSlug);
            $siblings = Task::where('tenant_id', $tenantId)
                ->where('status_slug', $statusSlug)
                ->where('id', '!=', $task->id)
                ->orderBy('board_position')
                ->get(['id', 'board_position']);
            $prev = $siblings[$index - 1] ?? null;
            $next = $siblings[$index] ?? null;
            $position = $this->calculatePosition($prev->board_position ?? null, $next->board_position ?? null);
        }

        $task->status = TaskStatus::stripPrefix($statusSlug);
        $task->status_slug = $statusSlug;
        $task->board_position = $position ?? 1000;
        $task->previous_status_slug = $previous;
        $task->save();
    }

    /**
     * Calculate position between two numbers using gap strategy.
     */
    protected function calculatePosition(?int $prev, ?int $next): ?int
    {
        $gap = 1000;
        if ($prev === null && $next === null) {
            return $gap;
        }
        if ($prev === null) {
            return $next - $gap;
        }
        if ($next === null) {
            return $prev + $gap;
        }
        $mid = intdiv($prev + $next, 2);
        if ($mid === $prev || $mid === $next) {
            return null;
        }
        return $mid;
    }

    /**
     * Resequence positions for a status column.
     */
    protected function resequence(int $tenantId, string $statusSlug): void
    {
        $pos = 1000;
        Task::where('tenant_id', $tenantId)
            ->where('status_slug', $statusSlug)
            ->orderBy('board_position')
            ->each(function (Task $t) use (&$pos) {
                $t->board_position = $pos;
                $t->save();
                $pos += 1000;
            });
    }
}
