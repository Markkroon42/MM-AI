<?php

namespace App\Services\Agents;

use App\Models\MetaCampaign;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CampaignAnalysisContextBuilder
{
    /**
     * Build analysis context for a campaign.
     *
     * @param MetaCampaign $campaign
     * @param int $days
     * @return array
     */
    public function buildContext(MetaCampaign $campaign, int $days = 7): array
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();

        // Load relationships
        $campaign->load(['metaAdAccount', 'adSets.ads']);

        // Get insights for the analysis window
        $insights = $campaign->insights()
            ->where('insight_date', '>=', $startDate)
            ->orderBy('insight_date', 'desc')
            ->get();

        // Aggregate metrics
        $aggregated = $insights->reduce(function ($carry, $insight) {
            return [
                'totalSpend' => $carry['totalSpend'] + ($insight->spend ?? 0),
                'totalImpressions' => $carry['totalImpressions'] + ($insight->impressions ?? 0),
                'totalClicks' => $carry['totalClicks'] + ($insight->clicks ?? 0),
                'totalPurchases' => $carry['totalPurchases'] + ($insight->purchases ?? 0),
                'totalPurchaseValue' => $carry['totalPurchaseValue'] + ($insight->purchase_value ?? 0),
                'totalReach' => $carry['totalReach'] + ($insight->reach ?? 0),
                'frequencySum' => $carry['frequencySum'] + ($insight->frequency ?? 0),
                'days' => $carry['days'] + 1,
            ];
        }, [
            'totalSpend' => 0,
            'totalImpressions' => 0,
            'totalClicks' => 0,
            'totalPurchases' => 0,
            'totalPurchaseValue' => 0,
            'totalReach' => 0,
            'frequencySum' => 0,
            'days' => 0,
        ]);

        // Calculate averages
        $avgCTR = $aggregated['totalImpressions'] > 0
            ? ($aggregated['totalClicks'] / $aggregated['totalImpressions']) * 100
            : 0;

        $avgCPC = $aggregated['totalClicks'] > 0
            ? $aggregated['totalSpend'] / $aggregated['totalClicks']
            : 0;

        $avgCPM = $aggregated['totalImpressions'] > 0
            ? ($aggregated['totalSpend'] / $aggregated['totalImpressions']) * 1000
            : 0;

        $avgROAS = $aggregated['totalSpend'] > 0
            ? $aggregated['totalPurchaseValue'] / $aggregated['totalSpend']
            : 0;

        $avgFrequency = $aggregated['days'] > 0
            ? $aggregated['frequencySum'] / $aggregated['days']
            : 0;

        // Calculate campaign age
        $campaignAge = $campaign->source_updated_at
            ? $campaign->source_updated_at->diffInDays(Carbon::now())
            : null;

        // Get last activity date
        $lastActivityDate = $insights->first()?->insight_date;

        // Count structures
        $adSetsCount = $campaign->adSets->count();
        $adsCount = $campaign->adSets->sum(fn($adSet) => $adSet->ads->count());

        // Build context array
        return [
            'campaign' => [
                'id' => $campaign->id,
                'meta_campaign_id' => $campaign->meta_campaign_id,
                'name' => $campaign->name,
                'status' => $campaign->status,
                'effective_status' => $campaign->effective_status,
                'objective' => $campaign->objective,
                'daily_budget' => $campaign->daily_budget,
                'lifetime_budget' => $campaign->lifetime_budget,
                'start_time' => $campaign->start_time,
                'stop_time' => $campaign->stop_time,
            ],
            'account' => $campaign->metaAdAccount ? [
                'id' => $campaign->metaAdAccount->id,
                'name' => $campaign->metaAdAccount->name,
                'meta_account_id' => $campaign->metaAdAccount->meta_account_id,
            ] : [
                'id' => null,
                'name' => 'Unknown Account',
                'meta_account_id' => null,
            ],
            'structure' => [
                'adSetsCount' => $adSetsCount,
                'adsCount' => $adsCount,
            ],
            'insights' => [
                'daysAnalyzed' => $days,
                'startDate' => $startDate->toDateString(),
                'endDate' => Carbon::now()->toDateString(),
                'recordsFound' => $insights->count(),
            ],
            'aggregated' => [
                'totalSpend' => round($aggregated['totalSpend'], 2),
                'totalImpressions' => $aggregated['totalImpressions'],
                'totalClicks' => $aggregated['totalClicks'],
                'totalPurchases' => $aggregated['totalPurchases'],
                'totalPurchaseValue' => round($aggregated['totalPurchaseValue'], 2),
                'totalReach' => $aggregated['totalReach'],
            ],
            'averages' => [
                'avgCTR' => round($avgCTR, 4),
                'avgCPC' => round($avgCPC, 4),
                'avgCPM' => round($avgCPM, 4),
                'avgROAS' => round($avgROAS, 4),
                'avgFrequency' => round($avgFrequency, 4),
            ],
            'metadata' => [
                'campaignAge' => $campaignAge,
                'lastActivityDate' => $lastActivityDate?->toDateString(),
            ],
        ];
    }
}
