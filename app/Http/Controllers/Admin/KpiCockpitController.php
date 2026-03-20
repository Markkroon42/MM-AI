<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExecutiveReport;
use App\Models\KpiSnapshot;
use App\Models\ScheduledTask;
use App\Models\SystemAlert;

class KpiCockpitController extends Controller
{
    public function index()
    {
        // Get latest KPI snapshot
        $latestSnapshot = KpiSnapshot::latest();

        // Get yesterday's snapshot for comparison
        $yesterdaySnapshot = KpiSnapshot::forDate(now()->subDay());

        // Get snapshots for last 7 days for trend charts
        $weeklySnapshots = KpiSnapshot::forDateRange(now()->subDays(7), now());

        // Get open alerts by severity
        $criticalAlerts = SystemAlert::open()->critical()->count();
        $openAlerts = SystemAlert::open()->count();

        // Get scheduled task health
        $unhealthyTasks = ScheduledTask::where('status', 'active')
            ->where('failure_count', '>=', 3)
            ->count();

        $activeTasks = ScheduledTask::where('status', 'active')->count();

        // Get latest executive report
        $latestReport = ExecutiveReport::where('status', 'completed')
            ->orderBy('generated_at', 'desc')
            ->first();

        // Top 3 campaigns by ROAS from latest snapshot
        $topCampaigns = $latestReport?->top_performers_json
            ? array_slice($latestReport->top_performers_json, 0, 3)
            : [];

        // Bottom 3 campaigns from latest snapshot
        $bottomCampaigns = $latestReport?->bottom_performers_json
            ? array_slice($latestReport->bottom_performers_json, 0, 3)
            : [];

        return view('admin.kpi-cockpit.index', compact(
            'latestSnapshot',
            'yesterdaySnapshot',
            'weeklySnapshots',
            'criticalAlerts',
            'openAlerts',
            'unhealthyTasks',
            'activeTasks',
            'latestReport',
            'topCampaigns',
            'bottomCampaigns'
        ));
    }
}
