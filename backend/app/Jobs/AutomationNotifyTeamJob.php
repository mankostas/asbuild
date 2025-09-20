<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Task;
use App\Models\Team;
use App\Support\PublicIdResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;

class AutomationNotifyTeamJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Task|int|string $taskIdentifier,
        public Team|int|string $teamIdentifier
    ) {
    }

    public function handle(): void
    {
        $task = $this->resolveModel(Task::class, $this->taskIdentifier);
        $team = $this->resolveModel(Team::class, $this->teamIdentifier);
        if (! $task || ! $team) {
            return;
        }
        foreach ($team->employees as $employee) {
            Notification::create([
                'user_id' => $employee->id,
                'category' => 'task',
                'message' => "Task {$task->public_id} status {$task->status_slug}",
                'link' => '/tasks/' . $task->public_id,
            ]);
        }
    }

    /**
     * @template TModel of Model
     * @param class-string<TModel> $modelClass
     * @param TModel|int|string $value
     * @return TModel|null
     */
    protected function resolveModel(string $modelClass, Model|int|string $value): ?Model
    {
        if ($value instanceof $modelClass) {
            return $value;
        }

        $id = app(PublicIdResolver::class)->resolve($modelClass, $value);

        if ($id === null) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        return $modelClass::find($id);
    }
}
