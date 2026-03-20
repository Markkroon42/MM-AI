<?php

namespace App\Services\Reporting;

use App\Models\ExecutiveReport;
use Illuminate\Support\Facades\Log;

class ExecutiveReportService
{
    public function __construct(
        protected ReportDataBuilder $dataBuilder
    ) {}

    /**
     * Generate daily summary report
     */
    public function generateDailySummary(?\Carbon\Carbon $date = null): ExecutiveReport
    {
        $date = $date ?? now()->subDay(); // Yesterday by default
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        Log::info('[EXECUTIVE_REPORT] Generating daily summary', [
            'date' => $date->toDateString(),
        ]);

        return $this->generateReport(
            reportType: 'daily_summary',
            periodStart: $startDate,
            periodEnd: $endDate
        );
    }

    /**
     * Generate weekly performance report
     */
    public function generateWeeklyPerformance(?\Carbon\Carbon $weekStart = null): ExecutiveReport
    {
        $weekStart = $weekStart ?? now()->subWeek()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        Log::info('[EXECUTIVE_REPORT] Generating weekly performance report', [
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
        ]);

        return $this->generateReport(
            reportType: 'weekly_performance',
            periodStart: $weekStart,
            periodEnd: $weekEnd
        );
    }

    /**
     * Generate report for custom period
     */
    public function generateReport(
        string $reportType,
        \Carbon\Carbon $periodStart,
        \Carbon\Carbon $periodEnd
    ): ExecutiveReport {
        $startTime = now();

        // Create report record
        $report = ExecutiveReport::create([
            'report_type' => $reportType,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'status' => 'generating',
        ]);

        Log::info('[EXECUTIVE_REPORT] Report creation started', [
            'report_id' => $report->id,
            'report_type' => $reportType,
        ]);

        try {
            // Build all report sections
            $headlineMetrics = $this->dataBuilder->buildHeadlineMetrics($periodStart, $periodEnd);
            $highlights = $this->dataBuilder->buildHighlights($periodStart, $periodEnd);
            $topPerformers = $this->dataBuilder->buildTopPerformers($periodStart, $periodEnd);
            $bottomPerformers = $this->dataBuilder->buildBottomPerformers($periodStart, $periodEnd);
            $issues = $this->dataBuilder->buildIssues();
            $priorities = $this->dataBuilder->buildPriorities($periodStart, $periodEnd);
            $executiveSummary = $this->dataBuilder->buildExecutiveSummary($headlineMetrics, $highlights, $issues);

            // Update report with all data
            $report->update([
                'status' => 'completed',
                'headline_metrics_json' => $headlineMetrics,
                'highlights_json' => $highlights,
                'top_performers_json' => $topPerformers,
                'bottom_performers_json' => $bottomPerformers,
                'issues_json' => $issues,
                'priorities_json' => $priorities,
                'executive_summary' => $executiveSummary,
                'generated_at' => now(),
                'generation_duration_seconds' => now()->diffInSeconds($startTime),
            ]);

            Log::info('[EXECUTIVE_REPORT] Report generated successfully', [
                'report_id' => $report->id,
                'duration_seconds' => $report->generation_duration_seconds,
            ]);

        } catch (\Exception $e) {
            $report->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'generation_duration_seconds' => now()->diffInSeconds($startTime),
            ]);

            Log::error('[EXECUTIVE_REPORT] Report generation failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $report->fresh();
    }

    /**
     * Get latest report of a specific type
     */
    public function getLatestReport(string $reportType): ?ExecutiveReport
    {
        return ExecutiveReport::where('report_type', $reportType)
            ->where('status', 'completed')
            ->orderBy('generated_at', 'desc')
            ->first();
    }

    /**
     * Get reports for date range
     */
    public function getReportsForPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        return ExecutiveReport::where('status', 'completed')
            ->whereBetween('period_start', [$start->toDateString(), $end->toDateString()])
            ->orderBy('period_start', 'desc')
            ->get();
    }
}
