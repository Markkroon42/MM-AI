<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentRun;
use App\Models\AiUsageLog;
use App\Models\Approval;
use App\Models\CampaignDraft;
use App\Models\CampaignRecommendation;
use App\Models\DraftEnrichment;
use App\Models\MetaAdAccount;
use App\Models\MetaCampaign;
use App\Models\MetaInsightDaily;
use App\Models\PublishJob;
use App\Models\SyncRun;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Total counts
        $totalAccounts = MetaAdAccount::where('is_active', true)->count();
        $totalCampaigns = MetaCampaign::whereIn('status', ['active', 'paused'])->count();
        
        // Today's spend
        $todaySpend = MetaInsightDaily::where('insight_date', Carbon::today())
            ->sum('spend');

        // This month's spend
        $monthSpend = MetaInsightDaily::whereMonth('insight_date', Carbon::now()->month)
            ->whereYear('insight_date', Carbon::now()->year)
            ->sum('spend');

        // Recent sync runs
        $recentSyncRuns = SyncRun::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Campaign status breakdown
        $campaignsByStatus = MetaCampaign::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Last 7 days spend trend
        $spendTrend = MetaInsightDaily::where('insight_date', '>=', Carbon::now()->subDays(7))
            ->selectRaw('insight_date, SUM(spend) as total_spend')
            ->groupBy('insight_date')
            ->orderBy('insight_date')
            ->get();

        // Recommendation stats
        $newRecommendations = CampaignRecommendation::where('status', 'new')->count();
        $highCriticalRecommendations = CampaignRecommendation::whereIn('status', ['new', 'reviewing'])
            ->whereIn('severity', ['high', 'critical'])
            ->count();
        $approvedRecommendationsLast7Days = CampaignRecommendation::where('status', 'approved')
            ->where('reviewed_at', '>=', Carbon::now()->subDays(7))
            ->count();

        // Recent agent runs
        $recentAgentRuns = AgentRun::with('recommendations')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Sprint 3: Approval and Publishing Stats
        $pendingApprovals = Approval::where('status', 'pending')->count();
        $approvedDraftsWaitingPublish = CampaignDraft::where('status', 'approved')->count();
        $failedPublishJobsLast7Days = PublishJob::where('status', 'failed')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
        $recentDrafts = CampaignDraft::with('briefing', 'template')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Sprint 4: AI Activity Stats
        $aiRunsToday = AiUsageLog::where('status', 'success')
            ->whereDate('created_at', Carbon::today())
            ->count();
        $aiRunsFailedToday = AiUsageLog::where('status', 'failed')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->count();
        $pendingEnrichments = DraftEnrichment::where('status', 'draft')->count();
        $aiCostLast7Days = AiUsageLog::where('status', 'success')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->sum('cost_estimate');

        return view('admin.dashboard', compact(
            'totalAccounts',
            'totalCampaigns',
            'todaySpend',
            'monthSpend',
            'recentSyncRuns',
            'campaignsByStatus',
            'spendTrend',
            'newRecommendations',
            'highCriticalRecommendations',
            'approvedRecommendationsLast7Days',
            'recentAgentRuns',
            'pendingApprovals',
            'approvedDraftsWaitingPublish',
            'failedPublishJobsLast7Days',
            'recentDrafts',
            'aiRunsToday',
            'aiRunsFailedToday',
            'pendingEnrichments',
            'aiCostLast7Days'
        ));
    }
}
