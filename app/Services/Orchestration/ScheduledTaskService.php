<?php

namespace App\Services\Orchestration;

use App\Models\ScheduledTask;
use Cron\CronExpression;
use Illuminate\Support\Facades\Log;

class ScheduledTaskService
{
    /**
     * Create a new scheduled task
     */
    public function create(array $data): ScheduledTask
    {
        // Calculate initial next_run_at
        if (isset($data['cron_expression'])) {
            $data['next_run_at'] = $this->calculateNextRunAt($data['cron_expression']);
        }

        $task = ScheduledTask::create($data);

        Log::info('[SCHEDULED_TASK_SERVICE] Created new scheduled task', [
            'task_id' => $task->id,
            'task_name' => $task->name,
            'task_type' => $task->task_type,
            'next_run_at' => $task->next_run_at,
        ]);

        return $task;
    }

    /**
     * Update a scheduled task
     */
    public function update(ScheduledTask $task, array $data): ScheduledTask
    {
        // Recalculate next_run_at if cron expression changed
        if (isset($data['cron_expression']) && $data['cron_expression'] !== $task->cron_expression) {
            $data['next_run_at'] = $this->calculateNextRunAt($data['cron_expression']);
        }

        $task->update($data);

        Log::info('[SCHEDULED_TASK_SERVICE] Updated scheduled task', [
            'task_id' => $task->id,
            'task_name' => $task->name,
        ]);

        return $task->fresh();
    }

    /**
     * Pause a task
     */
    public function pause(ScheduledTask $task): void
    {
        $task->update(['status' => 'paused']);

        Log::info('[SCHEDULED_TASK_SERVICE] Paused scheduled task', [
            'task_id' => $task->id,
            'task_name' => $task->name,
        ]);
    }

    /**
     * Resume a task
     */
    public function resume(ScheduledTask $task): void
    {
        $task->update([
            'status' => 'active',
            'next_run_at' => $this->calculateNextRunAt($task->cron_expression),
        ]);

        Log::info('[SCHEDULED_TASK_SERVICE] Resumed scheduled task', [
            'task_id' => $task->id,
            'task_name' => $task->name,
            'next_run_at' => $task->next_run_at,
        ]);
    }

    /**
     * Disable a task
     */
    public function disable(ScheduledTask $task): void
    {
        $task->update(['status' => 'disabled']);

        Log::info('[SCHEDULED_TASK_SERVICE] Disabled scheduled task', [
            'task_id' => $task->id,
            'task_name' => $task->name,
        ]);
    }

    /**
     * Calculate next run time based on cron expression
     */
    public function calculateNextRunAt(string $cronExpression, ?\DateTimeInterface $currentTime = null): \DateTimeInterface
    {
        $cron = new CronExpression($cronExpression);
        return $cron->getNextRunDate($currentTime ?? now());
    }

    /**
     * Get all due tasks that need to run
     */
    public function getDueTasks(): \Illuminate\Support\Collection
    {
        return ScheduledTask::where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at', 'asc')
            ->get();
    }

    /**
     * Mark task as run and calculate next run time
     */
    public function markAsRun(ScheduledTask $task, bool $success = true): void
    {
        $updates = [
            'last_run_at' => now(),
            'run_count' => $task->run_count + 1,
            'next_run_at' => $this->calculateNextRunAt($task->cron_expression),
        ];

        if ($success) {
            $updates['failure_count'] = 0;
        } else {
            $updates['failure_count'] = $task->failure_count + 1;
        }

        $task->update($updates);

        Log::info('[SCHEDULED_TASK_SERVICE] Marked task as run', [
            'task_id' => $task->id,
            'success' => $success,
            'failure_count' => $updates['failure_count'],
            'next_run_at' => $updates['next_run_at'],
        ]);
    }

    /**
     * Reset failure count
     */
    public function resetFailureCount(ScheduledTask $task): void
    {
        $task->update(['failure_count' => 0]);

        Log::info('[SCHEDULED_TASK_SERVICE] Reset failure count', [
            'task_id' => $task->id,
        ]);
    }
}
