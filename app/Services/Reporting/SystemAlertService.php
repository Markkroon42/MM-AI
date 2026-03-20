<?php

namespace App\Services\Reporting;

use App\Models\ScheduledTask;
use App\Models\ScheduledTaskRun;
use App\Models\SystemAlert;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SystemAlertService
{
    /**
     * Create alert for failed scheduled task
     */
    public function createScheduledTaskFailedAlert(ScheduledTask $task, ScheduledTaskRun $run, string $errorMessage): SystemAlert
    {
        // Check for existing open alert for this task to prevent duplicates
        $existing = SystemAlert::open()
            ->where('alert_type', 'scheduled_task_failed')
            ->where('related_entity_type', 'scheduled_task')
            ->where('related_entity_id', $task->id)
            ->first();

        if ($existing) {
            // Update existing alert with latest information
            $existing->update([
                'message' => "Task '{$task->name}' has failed {$task->failure_count} time(s). Latest error: {$errorMessage}",
                'context_json' => [
                    'task_id' => $task->id,
                    'run_id' => $run->id,
                    'failure_count' => $task->failure_count,
                    'error' => $errorMessage,
                    'last_failed_at' => $run->completed_at,
                ],
            ]);

            Log::info('[SYSTEM_ALERT] Updated existing scheduled task failed alert', [
                'alert_id' => $existing->id,
                'task_id' => $task->id,
            ]);

            return $existing;
        }

        // Create new alert
        $alert = SystemAlert::create([
            'alert_type' => 'scheduled_task_failed',
            'severity' => $task->failure_count >= 3 ? 'critical' : 'high',
            'title' => "Scheduled Task Failed: {$task->name}",
            'message' => "Task '{$task->name}' has failed. Error: {$errorMessage}",
            'status' => 'open',
            'related_entity_type' => 'scheduled_task',
            'related_entity_id' => $task->id,
            'context_json' => [
                'task_id' => $task->id,
                'run_id' => $run->id,
                'failure_count' => $task->failure_count,
                'error' => $errorMessage,
            ],
        ]);

        Log::info('[SYSTEM_ALERT] Created scheduled task failed alert', [
            'alert_id' => $alert->id,
            'task_id' => $task->id,
        ]);

        return $alert;
    }

    /**
     * Create alert for failed publish job
     */
    public function createPublishJobFailedAlert(int $publishJobId, string $errorMessage): SystemAlert
    {
        $existing = SystemAlert::open()
            ->where('alert_type', 'publish_job_failed')
            ->where('related_entity_type', 'publish_job')
            ->where('related_entity_id', $publishJobId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $alert = SystemAlert::create([
            'alert_type' => 'publish_job_failed',
            'severity' => 'high',
            'title' => 'Publish Job Failed',
            'message' => "Publish job #{$publishJobId} failed: {$errorMessage}",
            'status' => 'open',
            'related_entity_type' => 'publish_job',
            'related_entity_id' => $publishJobId,
            'context_json' => [
                'publish_job_id' => $publishJobId,
                'error' => $errorMessage,
            ],
        ]);

        Log::info('[SYSTEM_ALERT] Created publish job failed alert', [
            'alert_id' => $alert->id,
            'publish_job_id' => $publishJobId,
        ]);

        return $alert;
    }

    /**
     * Create alert for stale Meta sync
     */
    public function createStaleSyncAlert(int $hoursSinceLastSync): SystemAlert
    {
        $existing = SystemAlert::open()
            ->where('alert_type', 'stale_meta_sync')
            ->first();

        if ($existing) {
            $existing->update([
                'message' => "No Meta sync has run in the last {$hoursSinceLastSync} hours.",
                'context_json' => ['hours_since_last_sync' => $hoursSinceLastSync],
            ]);
            return $existing;
        }

        $alert = SystemAlert::create([
            'alert_type' => 'stale_meta_sync',
            'severity' => $hoursSinceLastSync >= 48 ? 'critical' : 'high',
            'title' => 'Stale Meta Sync Data',
            'message' => "No Meta sync has run in the last {$hoursSinceLastSync} hours.",
            'status' => 'open',
            'context_json' => ['hours_since_last_sync' => $hoursSinceLastSync],
        ]);

        Log::info('[SYSTEM_ALERT] Created stale sync alert', [
            'alert_id' => $alert->id,
            'hours' => $hoursSinceLastSync,
        ]);

        return $alert;
    }

    /**
     * Create alert for too many critical recommendations
     */
    public function createCriticalRecommendationsAlert(int $count): SystemAlert
    {
        $existing = SystemAlert::open()
            ->where('alert_type', 'critical_recommendations_threshold')
            ->first();

        if ($existing) {
            $existing->update([
                'message' => "There are {$count} critical recommendations pending review.",
                'context_json' => ['count' => $count],
            ]);
            return $existing;
        }

        $alert = SystemAlert::create([
            'alert_type' => 'critical_recommendations_threshold',
            'severity' => 'medium',
            'title' => 'Critical Recommendations Pending',
            'message' => "There are {$count} critical recommendations pending review.",
            'status' => 'open',
            'context_json' => ['count' => $count],
        ]);

        Log::info('[SYSTEM_ALERT] Created critical recommendations alert', [
            'alert_id' => $alert->id,
            'count' => $count,
        ]);

        return $alert;
    }

    /**
     * Create alert for old pending approvals
     */
    public function createOldApprovalsAlert(int $count, int $daysOld): SystemAlert
    {
        $existing = SystemAlert::open()
            ->where('alert_type', 'old_pending_approvals')
            ->first();

        if ($existing) {
            $existing->update([
                'message' => "There are {$count} approvals pending for more than {$daysOld} days.",
                'context_json' => ['count' => $count, 'days_old' => $daysOld],
            ]);
            return $existing;
        }

        $alert = SystemAlert::create([
            'alert_type' => 'old_pending_approvals',
            'severity' => 'medium',
            'title' => 'Old Pending Approvals',
            'message' => "There are {$count} approvals pending for more than {$daysOld} days.",
            'status' => 'open',
            'context_json' => ['count' => $count, 'days_old' => $daysOld],
        ]);

        Log::info('[SYSTEM_ALERT] Created old approvals alert', [
            'alert_id' => $alert->id,
            'count' => $count,
        ]);

        return $alert;
    }

    /**
     * Acknowledge an alert
     */
    public function acknowledge(SystemAlert $alert, User $user): SystemAlert
    {
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
        ]);

        Log::info('[SYSTEM_ALERT] Alert acknowledged', [
            'alert_id' => $alert->id,
            'user_id' => $user->id,
        ]);

        return $alert;
    }

    /**
     * Resolve an alert
     */
    public function resolve(SystemAlert $alert, User $user, ?string $notes = null): SystemAlert
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        Log::info('[SYSTEM_ALERT] Alert resolved', [
            'alert_id' => $alert->id,
            'user_id' => $user->id,
        ]);

        return $alert;
    }

    /**
     * Auto-resolve alerts when underlying issue is fixed
     */
    public function autoResolveForEntity(string $entityType, int $entityId, ?string $notes = null): int
    {
        $count = SystemAlert::open()
            ->where('related_entity_type', $entityType)
            ->where('related_entity_id', $entityId)
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolution_notes' => $notes ?? 'Auto-resolved: underlying issue fixed',
            ]);

        if ($count > 0) {
            Log::info('[SYSTEM_ALERT] Auto-resolved alerts', [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'count' => $count,
            ]);
        }

        return $count;
    }
}
