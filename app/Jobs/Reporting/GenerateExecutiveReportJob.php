<?php

namespace App\Jobs\Reporting;

use App\Services\Reporting\ExecutiveReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateExecutiveReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $reportType,
        public ?\Carbon\Carbon $periodStart = null,
        public ?\Carbon\Carbon $periodEnd = null
    ) {}

    public function handle(ExecutiveReportService $reportService): void
    {
        Log::info('[GENERATE_EXECUTIVE_REPORT_JOB] Starting report generation', [
            'report_type' => $this->reportType,
        ]);

        try {
            if ($this->reportType === 'daily_summary') {
                $reportService->generateDailySummary($this->periodStart);
            } elseif ($this->reportType === 'weekly_performance') {
                $reportService->generateWeeklyPerformance($this->periodStart);
            } else {
                $reportService->generateReport($this->reportType, $this->periodStart, $this->periodEnd);
            }

            Log::info('[GENERATE_EXECUTIVE_REPORT_JOB] Report generated successfully', [
                'report_type' => $this->reportType,
            ]);
        } catch (\Exception $e) {
            Log::error('[GENERATE_EXECUTIVE_REPORT_JOB] Report generation failed', [
                'report_type' => $this->reportType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
