<?php

namespace App\Services\Reporting;

use App\Models\Approval;
use App\Models\CampaignRecommendation;
use App\Models\MetaCampaign;
use App\Models\MetaInsightDaily;
use App\Models\PublishJob;
use App\Models\SystemAlert;
use Illuminate\Support\Facades\DB;

class ReportDataBuilder
{
    /**
     * Build headline metrics for a date range
     */
    public function buildHeadlineMetrics(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $insights = MetaInsightDaily::whereBetween('insight_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->select(
                DB::raw('SUM(spend) as total_spend'),
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(purchases) as total_conversions'),
                DB::raw('SUM(purchase_value) as total_revenue')
            )
            ->first();

        $totalSpend = (float) ($insights->total_spend ?? 0);
        $totalRevenue = (float) ($insights->total_revenue ?? 0);
        $totalClicks = (int) ($insights->total_clicks ?? 0);
        $totalImpressions = (int) ($insights->total_impressions ?? 0);

        $roas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;
        $cpc = $totalClicks > 0 ? $totalSpend / $totalClicks : 0;
        $ctr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;

        return [
            'total_spend' => round($totalSpend, 2),
            'total_revenue' => round($totalRevenue, 2),
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_conversions' => (int) ($insights->total_conversions ?? 0),
            'roas' => round($roas, 2),
            'cpc' => round($cpc, 4),
            'ctr' => round($ctr, 2),
            'active_campaigns' => MetaCampaign::where('status', 'ACTIVE')->count(),
        ];
    }

    /**
     * Build highlights (positive events/achievements)
     */
    public function buildHighlights(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $highlights = [];

        // Check for high ROAS campaigns
        $highRoasCampaigns = MetaInsightDaily::select('entity_local_id')
            ->where('entity_type', 'campaign')
            ->whereBetween('insight_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('entity_local_id')
            ->havingRaw('SUM(purchase_value) / NULLIF(SUM(spend), 0) > 3')
            ->havingRaw('SUM(spend) > 100')
            ->count();

        if ($highRoasCampaigns > 0) {
            $highlights[] = "{$highRoasCampaigns} campaign(s) achieved ROAS > 3.0";
        }

        // Check for executed recommendations
        $executedRecs = CampaignRecommendation::where('status', 'executed')
            ->whereBetween('executed_at', [$startDate, $endDate])
            ->count();

        if ($executedRecs > 0) {
            $highlights[] = "{$executedRecs} AI recommendation(s) successfully executed";
        }

        // Check for successful publishes
        $successfulPublishes = PublishJob::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($successfulPublishes > 0) {
            $highlights[] = "{$successfulPublishes} campaign(s) successfully published to Meta";
        }

        return $highlights;
    }

    /**
     * Build top performers
     */
    public function buildTopPerformers(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, int $limit = 5): array
    {
        $topCampaigns = MetaInsightDaily::select(
            'entity_local_id',
            DB::raw('SUM(spend) as total_spend'),
            DB::raw('SUM(purchase_value) as total_revenue'),
            DB::raw('SUM(purchases) as total_conversions'),
            DB::raw('SUM(purchase_value) / NULLIF(SUM(spend), 0) as roas')
        )
            ->where('entity_type', 'campaign')
            ->whereBetween('insight_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('entity_local_id')
            ->havingRaw('SUM(spend) > 50') // Minimum spend threshold
            ->orderByDesc('roas')
            ->limit($limit)
            ->get();

        return $topCampaigns->map(function ($item) {
            $campaign = MetaCampaign::find($item->entity_local_id);
            return [
                'campaign_id' => $item->entity_local_id,
                'campaign_name' => $campaign?->name ?? 'Unknown',
                'spend' => round((float) $item->total_spend, 2),
                'revenue' => round((float) $item->total_revenue, 2),
                'conversions' => (int) $item->total_conversions,
                'roas' => round((float) $item->roas, 2),
            ];
        })->toArray();
    }

    /**
     * Build bottom performers (campaigns needing attention)
     */
    public function buildBottomPerformers(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, int $limit = 5): array
    {
        $bottomCampaigns = MetaInsightDaily::select(
            'entity_local_id',
            DB::raw('SUM(spend) as total_spend'),
            DB::raw('SUM(purchase_value) as total_revenue'),
            DB::raw('SUM(purchases) as total_conversions'),
            DB::raw('SUM(purchase_value) / NULLIF(SUM(spend), 0) as roas')
        )
            ->where('entity_type', 'campaign')
            ->whereBetween('insight_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('entity_local_id')
            ->havingRaw('SUM(spend) > 50') // Minimum spend threshold
            ->orderBy('roas', 'asc')
            ->limit($limit)
            ->get();

        return $bottomCampaigns->map(function ($item) {
            $campaign = MetaCampaign::find($item->entity_local_id);
            return [
                'campaign_id' => $item->entity_local_id,
                'campaign_name' => $campaign?->name ?? 'Unknown',
                'spend' => round((float) $item->total_spend, 2),
                'revenue' => round((float) $item->total_revenue, 2),
                'conversions' => (int) $item->total_conversions,
                'roas' => round((float) $item->roas, 2),
            ];
        })->toArray();
    }

    /**
     * Build issues requiring attention
     */
    public function buildIssues(): array
    {
        $issues = [];

        // Critical open alerts
        $criticalAlerts = SystemAlert::open()->critical()->count();
        if ($criticalAlerts > 0) {
            $issues[] = "{$criticalAlerts} critical system alert(s) require immediate attention";
        }

        // Old pending approvals
        $oldApprovals = Approval::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(3))
            ->count();
        if ($oldApprovals > 0) {
            $issues[] = "{$oldApprovals} approval(s) pending for more than 3 days";
        }

        // Critical recommendations not reviewed
        $criticalRecs = CampaignRecommendation::where('status', 'new')
            ->where('severity', 'critical')
            ->count();
        if ($criticalRecs > 0) {
            $issues[] = "{$criticalRecs} critical recommendation(s) awaiting review";
        }

        // Failed publish jobs
        $failedJobs = PublishJob::where('status', 'failed')
            ->whereDate('created_at', '>', now()->subDays(7))
            ->count();
        if ($failedJobs > 0) {
            $issues[] = "{$failedJobs} publish job(s) failed in the last 7 days";
        }

        return $issues;
    }

    /**
     * Build recommended priorities/actions
     */
    public function buildPriorities(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $priorities = [];

        // Check for high-value recommendations
        $highValueRecs = CampaignRecommendation::whereIn('status', ['new', 'reviewing'])
            ->where('severity', 'critical')
            ->count();
        if ($highValueRecs > 0) {
            $priorities[] = "Review and act on {$highValueRecs} critical AI recommendations";
        }

        // Check for underperforming active campaigns
        $underperforming = MetaInsightDaily::select('entity_local_id')
            ->where('entity_type', 'campaign')
            ->whereBetween('insight_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('entity_local_id')
            ->havingRaw('SUM(purchase_value) / NULLIF(SUM(spend), 0) < 1')
            ->havingRaw('SUM(spend) > 100')
            ->count();

        if ($underperforming > 0) {
            $priorities[] = "Investigate {$underperforming} campaign(s) with ROAS < 1.0";
        }

        // Check for pending approvals
        $pendingApprovals = Approval::where('status', 'pending')->count();
        if ($pendingApprovals > 0) {
            $priorities[] = "Process {$pendingApprovals} pending approval(s)";
        }

        return $priorities;
    }

    /**
     * Build executive summary text
     */
    public function buildExecutiveSummary(array $metrics, array $highlights, array $issues): string
    {
        $roas = $metrics['roas'];
        $spend = number_format($metrics['total_spend'], 2);
        $revenue = number_format($metrics['total_revenue'], 2);
        $activeCampaigns = $metrics['active_campaigns'];

        $summary = "Performance Overview: {$activeCampaigns} active campaigns generated €{$revenue} revenue from €{$spend} spend, achieving {$roas}x ROAS.";

        if (!empty($highlights)) {
            $summary .= " Key highlights: " . implode(', ', array_slice($highlights, 0, 2)) . ".";
        }

        if (!empty($issues)) {
            $summary .= " Attention needed: " . implode(', ', array_slice($issues, 0, 2)) . ".";
        }

        return $summary;
    }
}
