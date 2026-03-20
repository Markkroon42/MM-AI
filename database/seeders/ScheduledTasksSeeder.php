<?php

namespace Database\Seeders;

use App\Models\ScheduledTask;
use App\Services\Orchestration\ScheduledTaskService;
use Illuminate\Database\Seeder;

class ScheduledTasksSeeder extends Seeder
{
    public function run(): void
    {
        $taskService = app(ScheduledTaskService::class);

        $tasks = [
            [
                'name' => 'Daily Performance Agent',
                'task_type' => 'run_agent',
                'description' => 'Run performance agent daily at 8 AM to analyze campaign performance and generate recommendations',
                'cron_expression' => '0 8 * * *', // 8 AM daily
                'run_context_json' => [
                    'agent_type' => 'performance',
                    'scope_type' => 'all',
                    'auto_approve' => false,
                ],
                'status' => 'active',
                'alert_on_failure' => true,
            ],
            [
                'name' => 'Weekly Structure Agent',
                'task_type' => 'run_agent',
                'description' => 'Run structure agent weekly on Mondays to analyze campaign structure',
                'cron_expression' => '0 9 * * 1', // 9 AM every Monday
                'run_context_json' => [
                    'agent_type' => 'structure',
                    'scope_type' => 'all',
                    'auto_approve' => false,
                ],
                'status' => 'active',
                'alert_on_failure' => true,
            ],
            [
                'name' => 'Morning Executive Report',
                'task_type' => 'generate_report',
                'description' => 'Generate daily executive summary report every morning at 9 AM',
                'cron_expression' => '0 9 * * *', // 9 AM daily
                'run_context_json' => [
                    'report_type' => 'daily_summary',
                    'period_days' => 1,
                    'include_recommendations' => true,
                ],
                'status' => 'active',
                'alert_on_failure' => true,
            ],
            [
                'name' => 'Weekly Performance Report',
                'task_type' => 'generate_report',
                'description' => 'Generate weekly performance report every Monday morning',
                'cron_expression' => '0 10 * * 1', // 10 AM every Monday
                'run_context_json' => [
                    'report_type' => 'weekly_performance',
                    'period_days' => 7,
                    'include_recommendations' => true,
                ],
                'status' => 'active',
                'alert_on_failure' => true,
            ],
            [
                'name' => 'Nightly KPI Snapshot',
                'task_type' => 'create_kpi_snapshot',
                'description' => 'Create daily KPI snapshot at end of day (11:59 PM)',
                'cron_expression' => '59 23 * * *', // 11:59 PM daily
                'run_context_json' => [
                    'snapshot_date' => null,
                    'include_trends' => true,
                ],
                'status' => 'active',
                'alert_on_failure' => true,
            ],
            [
                'name' => 'Hourly Meta Sync',
                'task_type' => 'sync_meta',
                'description' => 'Sync Meta campaign data every hour',
                'cron_expression' => '0 * * * *', // Top of every hour
                'run_context_json' => [
                    'entity_type' => 'campaigns',
                    'include_insights' => true,
                ],
                'status' => 'active',
                'alert_on_failure' => true,
            ],
        ];

        foreach ($tasks as $taskData) {
            ScheduledTask::firstOrCreate(
                [
                    'name' => $taskData['name'],
                ],
                $taskData
            );
        }
    }
}
