<?php

namespace App\Console\Commands\Reporting;

use App\Services\Reporting\ExecutiveReportService;
use Illuminate\Console\Command;

class GenerateDailyExecutiveReportCommand extends Command
{
    protected $signature = 'reports:generate-daily-executive {--date= : Date for report (Y-m-d format)}';
    protected $description = 'Generate daily executive summary report';

    public function handle(ExecutiveReportService $reportService): int
    {
        $date = $this->option('date')
            ? \Carbon\Carbon::parse($this->option('date'))
            : now()->subDay();

        $this->info("Generating daily executive report for {$date->toDateString()}...");

        try {
            $report = $reportService->generateDailySummary($date);

            $this->info("Report generated successfully!");
            $this->info("Report ID: {$report->id}");
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
