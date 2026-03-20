<?php

namespace App\Jobs\Orchestration;

use App\Models\ScheduledTask;
use App\Services\Orchestration\ScheduledTaskRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunScheduledTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ScheduledTask $task
    ) {}

    public function handle(ScheduledTaskRunner $runner): void
    {
        Log::info('[RUN_SCHEDULED_TASK_JOB] Starting task execution', [
            'task_id' => $this->task->id,
            'task_name' => $this->task->name,
        ]);

        try {
            $runner->run($this->task);
        } catch (\Exception $e) {
            Log::error('[RUN_SCHEDULED_TASK_JOB] Task execution failed', [
                'task_id' => $this->task->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
