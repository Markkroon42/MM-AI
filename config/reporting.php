<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Reporting Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for executive reports and KPI snapshots.
    |
    */

    'executive_reports' => [
        // Report generation windows
        'daily_summary' => [
            'enabled' => true,
            'period_days' => 1,
            'include_recommendations' => true,
            'include_top_performers' => 5,
            'include_bottom_performers' => 5,
        ],

        'weekly_performance' => [
            'enabled' => true,
            'period_days' => 7,
            'include_recommendations' => true,
            'include_top_performers' => 10,
            'include_bottom_performers' => 10,
        ],

        // When to generate reports
        'schedule' => [
            'daily_summary_time' => '09:00', // 9 AM
            'weekly_summary_day' => 'monday', // Day of week
            'weekly_summary_time' => '10:00', // 10 AM
        ],

        // Retention
        'retention_days' => 90, // Keep reports for 90 days
    ],

    'kpi_snapshots' => [
        // When to create snapshots
        'schedule' => [
            'time' => '23:59', // End of day
        ],

        // Retention
        'retention_days' => 365, // Keep snapshots for 1 year

        // What to include
        'include_metrics' => [
            'campaign_counts' => true,
            'spend_metrics' => true,
            'performance_metrics' => true,
            'recommendation_metrics' => true,
            'system_health_metrics' => true,
        ],
    ],

    'system_alerts' => [
        // Alert thresholds
        'thresholds' => [
            'stale_sync_hours' => 24, // Alert if no sync in 24 hours
            'critical_recommendations_count' => 5, // Alert if > 5 critical recs
            'old_approvals_days' => 3, // Alert if approvals > 3 days old
            'failed_publish_jobs_count' => 3, // Alert if > 3 failed jobs in 24h
            'scheduled_task_failures' => 3, // Alert if task fails 3 times
        ],

        // Auto-resolution
        'auto_resolve' => true, // Auto-resolve when issue fixed

        // Retention
        'retention_days' => 30, // Keep resolved alerts for 30 days
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Widgets
    |--------------------------------------------------------------------------
    |
    | Configuration for dashboard KPI displays.
    |
    */

    'dashboard' => [
        'refresh_interval' => 60, // Seconds between auto-refresh
        'trend_comparison_days' => 1, // Compare to yesterday
        'show_alerts' => true,
        'show_scheduled_tasks' => true,
        'show_latest_report' => true,
    ],
];
