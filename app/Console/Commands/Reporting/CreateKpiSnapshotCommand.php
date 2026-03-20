<?php

namespace App\Console\Commands\Reporting;

use App\Services\Reporting\KpiSnapshotService;
use Illuminate\Console\Command;

class CreateKpiSnapshotCommand extends Command
{
    protected $signature = 'kpi:snapshot {--date= : Date for snapshot (Y-m-d format)}';
    protected $description = 'Create daily KPI snapshot';

    public function handle(KpiSnapshotService $kpiSnapshotService): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : now();

        $this->info("Creating KPI snapshot for {$date->toDateString()}...");

        try {
            $snapshot = $kpiSnapshotService->createDailySnapshot($date);

            $this->info("KPI snapshot created successfully!");
            $this->info("Snapshot ID: {$snapshot->id}");
            $this->info("Active Campaigns: {$snapshot->active_campaigns_count}");
            $this->info("Total Spend: €{$snapshot->total_spend}");
            $this->info("Total Revenue: €{$snapshot->total_revenue}");
            $this->info("ROAS: {$snapshot->avg_roas}x");
            $this->info("Open Alerts: {$snapshot->open_alerts_count}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create snapshot: {$e->getMessage()}");
            return 1;
        }
    }
}
