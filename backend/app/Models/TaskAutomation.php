<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Jobs\AutomationNotifyTeamJob;
use App\Models\Task;
use App\Models\TaskStatus;

class TaskAutomation extends Model
{
    protected $fillable = [
        'task_type_id',
        'event',
        'conditions_json',
        'actions_json',
        'enabled',
    ];

    protected $casts = [
        'conditions_json' => 'array',
        'actions_json' => 'array',
        'enabled' => 'boolean',
    ];

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }

    public static function run(Task $task, string $event): void
    {
        $rules = static::where('task_type_id', $task->task_type_id)
            ->where('event', $event)
            ->where('enabled', true)
            ->get();

        foreach ($rules as $rule) {
            $conditions = $rule->conditions_json ?? [];
            if (
                isset($conditions['status'])
                && TaskStatus::stripPrefix($task->status_slug) !== TaskStatus::stripPrefix($conditions['status'])
            ) {
                continue;
            }
            foreach ($rule->actions_json as $action) {
                if (($action['type'] ?? null) === 'notify_team' && isset($action['team_id'])) {
                    AutomationNotifyTeamJob::dispatch($task->id, $action['team_id']);
                }
            }
        }
    }
}
