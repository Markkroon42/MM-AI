<?php

namespace App\Console\Commands\Reporting;

use App\Services\Reporting\ExecutiveReportService;
use Illuminate\Console\Command;

class GenerateWeeklyPerformanceReportCommand extends Command
{
    protected $signature = 'reports:generate-weekly-performance {--week-start= : Week start date (Y-m-d format)}';
    protected $description = 'Generate weekly performance report';

    public function handle(ExecutiveReportService $reportService): int
    {
        $weekStart = $this->option('week-start')
            ? \Carbon\Carbon::parse($this->option('week-start'))
            : now()->subWeek()->startOfWeek();

        $this->info("Generating weekly performance report starting {$weekStart->toDateString()}...");

        try {
            $report = $reportService->generateWeeklyPerformance($weekStart);

            $this->info("Report generated successfully!");
            $this->info("Report ID: {$report->id}");
            $this->info("Period: {$report->period_label}");
            $this->info("Total Spend: €{$report->headline_metrics_json['total_spend']}");
            $this->info("Total Revenue: €{$report->headline_metrics_json['total_revenue']}");
            $this->info("ROAS: {$report->headline_metrics_json['roas']}x");

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to generate report: {$e->getMessage()}");
            return 1;
        }
    }
}
