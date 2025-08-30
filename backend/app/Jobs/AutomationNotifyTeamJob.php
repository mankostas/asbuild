<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\Team;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutomationNotifyTeamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $taskId, public int $teamId)
    {
    }

    public function handle(): void
    {
        $task = Task::find($this->taskId);
        $team = Team::find($this->teamId);
        if (! $task || ! $team) {
            return;
        }
        foreach ($team->employees as $employee) {
            Notification::create([
                'user_id' => $employee->id,
                'category' => 'task',
                'message' => "Task {$task->id} status {$task->status_slug}",
            ]);
        }
    }
}
