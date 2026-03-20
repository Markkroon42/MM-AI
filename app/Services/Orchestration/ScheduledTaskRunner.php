<?php

namespace App\Services\Orchestration;

use App\Enums\ScheduledTaskTypeEnum;
use App\Jobs\Meta\SyncMetaCampaignsJob;
use App\Models\ScheduledTask;
use App\Models\ScheduledTaskRun;
use App\Services\Agents\PerformanceAgentService;
use App\Services\Agents\StructureAgentService;
use App\Services\Reporting\ExecutiveReportService;
use App\Services\Reporting\KpiSnapshotService;
use App\Services\Reporting\SystemAlertService;
use Illuminate\Support\Facades\Log;

class ScheduledTaskRunner
{
    public function __construct(
        protected ScheduledTaskService $scheduledTaskService,
        protected TaskContextResolver $contextResolver,
        protected SystemAlertService $alertService,
        protected PerformanceAgentService $performanceAgentService,
        protected StructureAgentService $structureAgentService,
        protected ExecutiveReportService $reportService,
        protected KpiSnapshotService $kpiSnapshotService,
    ) {}

    /**
     * Run a specific scheduled task
     */
    public function run(ScheduledTask $task): ScheduledTaskRun
    {
        Log::info('[SCHEDULED_TASK_RUNNER] Starting task execution', [
            'task_id' => $task->id,
            'task_name' => $task->name,
            'task_type' => $task->task_type,
        ]);

        // Create run record
        $run = ScheduledTaskRun::create([
            'scheduled_task_id' => $task->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            // Resolve context
            $context = $this->contextResolver->resolve($task->task_type, $task->run_context_json);

            // Execute based on task type
            $result = $this->executeTaskByType($task->task_type, $context);

            // Mark as completed
            $completedAt = now();
            $run->update([
                'status' => 'completed',
                'completed_at' => $completedAt,
                'duration_seconds' => $completedAt->diffInSeconds($run->started_at),
                'result_summary' => $result['summary'] ?? 'Task completed successfully',
                'result_data_json' => $result['data'] ?? null,
            ]);

            // Mark task as successfully run
            $this->scheduledTaskService->markAsRun($task, true);

            Log::info('[SCHEDULED_TASK_RUNNER] Task completed successfully', [
                'task_id' => $task->id,
                'run_id' => $run->id,
                'duration_seconds' => $run->duration_seconds,
            ]);

        } catch (\Exception $e) {
            // Mark as failed
            $completedAt = now();
            $run->update([
                'status' => 'failed',
                'completed_at' => $completedAt,
                'duration_seconds' => $completedAt->diffInSeconds($run->started_at),
                'error_message' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            // Mark task as failed
            $this->scheduledTaskService->markAsRun($task, false);

            // Create alert if configured
            if ($task->alert_on_failure) {
                $this->alertService->createScheduledTaskFailedAlert($task, $run, $e->getMessage());
            }

            Log::error('[SCHEDULED_TASK_RUNNER] Task execution failed', [
                'task_id' => $task->id,
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $run->fresh();
    }

    /**
     * Execute task based on type
     */
    protected function executeTaskByType(string $taskType, array $context): array
    {
        return match ($taskType) {
            ScheduledTaskTypeEnum::RUN_AGENT->value => $this->executeAgent($context),
            ScheduledTaskTypeEnum::GENERATE_REPORT->value => $this->executeReportGeneration($context),
            ScheduledTaskTypeEnum::CREATE_KPI_SNAPSHOT->value => $this->executeKpiSnapshot($context),
            ScheduledTaskTypeEnum::SYNC_META->value => $this->executeSyncMeta($context),
            default => throw new \Exception("Unsupported task type: {$taskType}"),
        };
    }

    /**
     * Execute agent task
     */
    protected function executeAgent(array $context): array
    {
        $agentType = $context['agent_type'];
        $scopeType = $context['scope_type'];
        $scopeId = $context['scope_id'];

        Log::info('[SCHEDULED_TASK_RUNNER] Executing agent', [
            'agent_type' => $agentType,
            'scope_type' => $scopeType,
        ]);

        if ($agentType === 'performance') {
            $run = $this->performanceAgentService->analyze($scopeType, $scopeId);
            $recommendationsCount = $run->recommendations()->count();

            return [
                'summary' => "Performance agent completed: {$recommendationsCount} recommendations generated",
                'data' => [
                    'agent_run_id' => $run->id,
                    'recommendations_count' => $recommendationsCount,
                ],
            ];
        }

        if ($agentType === 'structure') {
            $run = $this->structureAgentService->analyze($scopeType, $scopeId);
            $recommendationsCount = $run->recommendations()->count();

            return [
                'summary' => "Structure agent completed: {$recommendationsCount} recommendations generated",
                'data' => [
                    'agent_run_id' => $run->id,
                    'recommendations_count' => $recommendationsCount,
                ],
            ];
        }

        throw new \Exception("Unsupported agent type: {$agentType}");
    }

    /**
     * Execute report generation
     */
    protected function executeReportGeneration(array $context): array
    {
        $reportType = $context['report_type'];
        $periodDays = $context['period_days'];

        Log::info('[SCHEDULED_TASK_RUNNER] Generating report', [
            'report_type' => $reportType,
            'period_days' => $periodDays,
        ]);

        if ($reportType === 'daily_summary') {
            $report = $this->reportService->generateDailySummary();
        } elseif ($reportType === 'weekly_performance') {
            $report = $this->reportService->generateWeeklyPerformance();
        } else {
            throw new \Exception("Unsupported report type: {$reportType}");
        }

        return [
            'summary' => "Report generated: {$report->report_type}",
            'data' => [
                'report_id' => $report->id,
                'report_type' => $report->report_type,
            ],
        ];
    }

    /**
     * Execute KPI snapshot
     */
    protected function executeKpiSnapshot(array $context): array
    {
        Log::info('[SCHEDULED_TASK_RUNNER] Creating KPI snapshot');

        $snapshot = $this->kpiSnapshotService->createDailySnapshot();

        return [
            'summary' => "KPI snapshot created for {$snapshot->snapshot_date->format('Y-m-d')}",
            'data' => [
                'snapshot_id' => $snapshot->id,
                'snapshot_date' => $snapshot->snapshot_date->format('Y-m-d'),
                'total_spend' => $snapshot->total_spend,
                'total_revenue' => $snapshot->total_revenue,
            ],
        ];
    }

    /**
     * Execute Meta sync
     */
    protected function executeSyncMeta(array $context): array
    {
        Log::info('[SCHEDULED_TASK_RUNNER] Syncing Meta data', [
            'entity_type' => $context['entity_type'],
        ]);

        // Dispatch sync job
        SyncMetaCampaignsJob::dispatch();

        return [
            'summary' => 'Meta sync job dispatched',
            'data' => [
                'entity_type' => $context['entity_type'],
            ],
        ];
    }

    /**
     * Run all due tasks
     */
    public function runDueTasks(): int
    {
        $dueTasks = $this->scheduledTaskService->getDueTasks();
        $count = $dueTasks->count();

        Log::info('[SCHEDULED_TASK_RUNNER] Running due tasks', [
            'count' => $count,
        ]);

        foreach ($dueTasks as $task) {
            try {
                $this->run($task);
            } catch (\Exception $e) {
                Log::error('[SCHEDULED_TASK_RUNNER] Failed to run task', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
