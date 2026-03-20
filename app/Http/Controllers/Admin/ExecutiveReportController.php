<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExecutiveReport;
use App\Services\Reporting\ExecutiveReportService;
use Illuminate\Http\Request;

class ExecutiveReportController extends Controller
{
    public function __construct(
        protected ExecutiveReportService $reportService
    ) {}

    public function index()
    {
        $reports = ExecutiveReport::orderBy('period_start', 'desc')
            ->paginate(20);

        return view('admin.executive-reports.index', compact('reports'));
    }

    public function show(ExecutiveReport $report)
    {
        return view('admin.executive-reports.show', compact('report'));
    }

    public function generateDaily(Request $request)
    {
        $date = $request->input('date')
            ? \Carbon\Carbon::parse($request->input('date'))
            : now()->subDay();

        try {
            $report = $this->reportService->generateDailySummary($date);

            return redirect()->route('admin.executive-reports.show', $report)
                ->with('success', 'Daily executive report generated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.executive-reports.index')
                ->with('error', "Failed to generate report: {$e->getMessage()}");
        }
    }

    public function generateWeekly(Request $request)
    {
        $weekStart = $request->input('week_start')
            ? \Carbon\Carbon::parse($request->input('week_start'))
            : now()->subWeek()->startOfWeek();

        try {
            $report = $this->reportService->generateWeeklyPerformance($weekStart);

            return redirect()->route('admin.executive-reports.show', $report)
                ->with('success', 'Weekly performance report generated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.executive-reports.index')
                ->with('error', "Failed to generate report: {$e->getMessage()}");
        }
    }
}
