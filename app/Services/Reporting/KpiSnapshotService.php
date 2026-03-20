<?php

namespace App\Services\Reporting;

use App\Models\Approval;
use App\Models\CampaignRecommendation;
use App\Models\KpiSnapshot;
use App\Models\MetaAd;
use App\Models\MetaAdSet;
use App\Models\MetaCampaign;
use App\Models\MetaInsightDaily;
use App\Models\PublishJob;
use App\Models\SystemAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KpiSnapshotService
{
    /**
     * Create daily KPI snapshot
     */
    public function createDailySnapshot(?\Carbon\Carbon $date = null): KpiSnapshot
    {
        $snapshotDate = $date ?? now();
        $dateString = $snapshotDate->toDateString();

        Log::info('[KPI_SNAPSHOT] Creating daily snapshot', [
            'date' => $dateString,
        ]);

        // Check if snapshot already exists
        $existing = KpiSnapshot::where('snapshot_date', $dateString)->first();
        if ($existing) {
            Log::info('[KPI_SNAPSHOT] Snapshot already exists, updating', [
                'snapshot_id' => $existing->id,
            ]);
            return $this->updateSnapshot($existing);
        }

        // Collect metrics
        $metrics = $this->collectMetrics($snapshotDate);

        // Create snapshot
        $snapshot = KpiSnapshot::create(array_merge([
            'snapshot_date' => $dateString,
        ], $metrics));

        Log::info('[KPI_SNAPSHOT] Snapshot created', [
            'snapshot_id' => $snapshot->id,
            'total_spend' => $snapshot->total_spend,
            'total_revenue' => $snapshot->total_revenue,
        ]);

        return $snapshot;
    }

    /**
     * Update existing snapshot
     */
    protected function updateSnapshot(KpiSnapshot $snapshot): KpiSnapshot
    {
        $metrics = $this->collectMetrics($snapshot->snapshot_date);
        $snapshot->update($metrics);

        Log::info('[KPI_SNAPSHOT] Snapshot updated', [
            'snapshot_id' => $snapshot->id,
        ]);

        return $snapshot->fresh();
    }

    /**
     * Collect all metrics for snapshot
     */
    protected function collectMetrics(\Carbon\Carbon $date): array
    {
        // Active entity counts
        $activeCampaigns = MetaCampaign::where('status', 'ACTIVE')->count();
        $activeAdSets = MetaAdSet::where('status', 'ACTIVE')->count();
        $activeAds = MetaAd::where('status', 'ACTIVE')->count();

        // Get insights for the date
        $insights = MetaInsightDaily::where('insight_date', $date->toDateString())
            ->select(
                DB::raw('SUM(spend) as total_spend'),
                DB::raw('SUM(impressions) as total_impressions'),
                DB::raw('SUM(clicks) as total_clicks'),
                DB::raw('SUM(purchases) as total_conversions'),
                DB::raw('SUM(purchase_value) as total_revenue')
            )
            ->first();

        $totalSpend = (float) ($insights->total_spend ?? 0);
        $totalImpressions = (int) ($insights->total_impressions ?? 0);
        $totalClicks = (int) ($insights->total_clicks ?? 0);
        $totalConversions = (int) ($insights->total_conversions ?? 0);
        $totalRevenue = (float) ($insights->total_revenue ?? 0);

        // Calculate averages
        $avgCpc = $totalClicks > 0 ? $totalSpend / $totalClicks : 0;
        $avgCtr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
        $avgRoas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;

        // Recommendation counts
        $pendingRecommendations = CampaignRecommendation::whereIn('status', ['new', 'reviewing'])->count();
        $approvedRecommendations = CampaignRecommendation::where('status', 'approved')->count();
        $executedRecommendations = CampaignRecommendation::where('status', 'executed')
            ->whereDate('executed_at', $date->toDateString())
            ->count();

        // Approval and job counts
        $pendingApprovals = Approval::where('status', 'pending')->count();
        $pendingPublishJobs = PublishJob::whereIn('status', ['pending', 'running'])->count();

        // Open alerts count
        $openAlerts = SystemAlert::where('status', 'open')->count();

        return [
            'active_campaigns_count' => $activeCampaigns,
            'active_ad_sets_count' => $activeAdSets,
            'active_ads_count' => $activeAds,
            'total_spend' => $totalSpend,
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'avg_cpc' => round($avgCpc, 4),
            'avg_ctr' => round($avgCtr, 4),
            'total_conversions' => $totalConversions,
            'total_revenue' => $totalRevenue,
            'avg_roas' => round($avgRoas, 4),
            'pending_recommendations_count' => $pendingRecommendations,
            'approved_recommendations_count' => $approvedRecommendations,
            'executed_recommendations_count' => $executedRecommendations,
            'pending_approvals_count' => $pendingApprovals,
            'pending_publish_jobs_count' => $pendingPublishJobs,
            'open_alerts_count' => $openAlerts,
        ];
    }

    /**
     * Get trend comparison between two dates
     */
    public function getTrend(string $metric, \Carbon\Carbon $currentDate, \Carbon\Carbon $previousDate): ?array
    {
        $current = KpiSnapshot::forDate($currentDate);
        $previous = KpiSnapshot::forDate($previousDate);

        if (!$current || !$previous) {
            return null;
        }

        $currentValue = $current->$metric ?? 0;
        $previousValue = $previous->$metric ?? 0;

        if ($previousValue == 0) {
            return [
                'current' => $currentValue,
                'previous' => $previousValue,
                'change' => $currentValue,
                'change_percentage' => 0,
                'direction' => 'flat',
            ];
        }

        $change = $currentValue - $previousValue;
        $changePercentage = ($change / $previousValue) * 100;

        return [
            'current' => $currentValue,
            'previous' => $previousValue,
            'change' => $change,
            'change_percentage' => round($changePercentage, 2),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
        ];
    }
}
