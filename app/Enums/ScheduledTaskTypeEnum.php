<?php

namespace App\Enums;

enum ScheduledTaskTypeEnum: string
{
    case RUN_AGENT = 'run_agent';
    case GENERATE_REPORT = 'generate_report';
    case CREATE_KPI_SNAPSHOT = 'create_kpi_snapshot';
    case SYNC_META = 'sync_meta';
    case CLEANUP_OLD_DATA = 'cleanup_old_data';

    public function label(): string
    {
        return match($this) {
            self::RUN_AGENT => 'Run Agent',
            self::GENERATE_REPORT => 'Generate Report',
            self::CREATE_KPI_SNAPSHOT => 'Create KPI Snapshot',
            self::SYNC_META => 'Sync Meta Data',
            self::CLEANUP_OLD_DATA => 'Cleanup Old Data',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::RUN_AGENT => 'Execute AI agent for campaign analysis',
            self::GENERATE_REPORT => 'Generate executive or performance report',
            self::CREATE_KPI_SNAPSHOT => 'Create daily KPI snapshot',
            self::SYNC_META => 'Synchronize data from Meta Ads',
            self::CLEANUP_OLD_DATA => 'Clean up old logs and historical data',
        };
    }
}
