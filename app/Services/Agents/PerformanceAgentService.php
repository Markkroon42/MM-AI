<?php

namespace App\Services\Agents;

use App\Enums\AgentRunStatusEnum;
use App\Enums\AgentScopeTypeEnum;
use App\Enums\RecommendationSeverityEnum;
use App\Enums\RecommendationTypeEnum;
use App\Models\AgentRun;
use App\Models\MetaCampaign;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PerformanceAgentService
{
    public function __construct(
        private RecommendationFactory $factory,
        private CampaignAnalysisContextBuilder $contextBuilder,
    ) {}

    /**
     * Analyze campaign performance and return recommendations.
     *
     * @param MetaCampaign $campaign
     * @param int $days
     * @return array
     */
    public function analyzeCampaign(MetaCampaign $campaign, int $days = 7): array
    {
        $recommendations = [];

        // Create agent run record
        $agentRun = AgentRun::create([
            'agent_name' => 'performance_agent',
            'scope_type' => AgentScopeTypeEnum::CAMPAIGN->value,
            'scope_id' => $campaign->id,
            'status' => AgentRunStatusEnum::RUNNING->value,
            'started_at' => Carbon::now(),
            'input_payload_json' => [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'analysis_days' => $days,
            ],
        ]);

        Log::info('[PERFORMANCE_AGENT] Starting analysis', [
            'run_id' => $agentRun->id,
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'days' => $days,
        ]);

        try {
            // Build analysis context
            $context = $this->contextBuilder->buildContext($campaign, $days);

            $minSpend = config('recommendations.min_spend_for_serious_evaluation', 50);

            // Only run performance checks if minimum spend is met
            if ($context['aggregated']['totalSpend'] >= $minSpend) {

                // 1. Check for low CTR
                $lowCtrCheck = $this->checkLowCtr($campaign, $context, $agentRun->id);
                if ($lowCtrCheck) {
                    $recommendations[] = $lowCtrCheck;
                }

                // 2. Check for high CPC
                $highCpcCheck = $this->checkHighCpc($campaign, $context, $agentRun->id);
                if ($highCpcCheck) {
                    $recommendations[] = $highCpcCheck;
                }

                // 3. Check for low ROAS
                $lowRoasCheck = $this->checkLowRoas($campaign, $context, $agentRun->id);
                if ($lowRoasCheck) {
                    $recommendations[] = $lowRoasCheck;
                }

                // 4. Check for high frequency
                $highFrequencyCheck = $this->checkHighFrequency($campaign, $context, $agentRun->id);
                if ($highFrequencyCheck) {
                    $recommendations[] = $highFrequencyCheck;
                }

                // 5. Check for spend without purchases
                $noPurchasesCheck = $this->checkSpendWithoutPurchases($campaign, $context, $agentRun->id);
                if ($noPurchasesCheck) {
                    $recommendations[] = $noPurchasesCheck;
                }

                // 6. Check for budget underutilization
                $budgetCheck = $this->checkBudgetUnderutilized($campaign, $context, $agentRun->id, $days);
                if ($budgetCheck) {
                    $recommendations[] = $budgetCheck;
                }

                // 7. Check for scale winner opportunity
                $scaleCheck = $this->checkScaleWinner($campaign, $context, $agentRun->id);
                if ($scaleCheck) {
                    $recommendations[] = $scaleCheck;
                }

                // 8. Check for pause loser recommendation
                $pauseCheck = $this->checkPauseLoser($campaign, $context, $agentRun->id);
                if ($pauseCheck) {
                    $recommendations[] = $pauseCheck;
                }

                // 9. Check for creative fatigue
                $fatigueCheck = $this->checkCreativeFatigue($campaign, $context, $agentRun->id);
                if ($fatigueCheck) {
                    $recommendations[] = $fatigueCheck;
                }

            } else {
                Log::info('[PERFORMANCE_AGENT] Insufficient spend for analysis', [
                    'run_id' => $agentRun->id,
                    'total_spend' => $context['aggregated']['totalSpend'],
                    'min_required' => $minSpend,
                ]);
            }

            // Update agent run as successful
            $agentRun->update([
                'status' => AgentRunStatusEnum::SUCCESS->value,
                'finished_at' => Carbon::now(),
                'output_payload_json' => [
                    'recommendations_found' => count($recommendations),
                    'checks_performed' => 9,
                    'context' => $context,
                ],
            ]);

            Log::info('[PERFORMANCE_AGENT] Analysis completed successfully', [
                'run_id' => $agentRun->id,
                'recommendations_count' => count($recommendations),
            ]);

        } catch (\Exception $e) {
            // Update agent run as failed
            $agentRun->update([
                'status' => AgentRunStatusEnum::FAILED->value,
                'finished_at' => Carbon::now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error('[PERFORMANCE_AGENT] Analysis failed', [
                'run_id' => $agentRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $recommendations;
    }

    /**
     * Check for low CTR.
     */
    private function checkLowCtr(MetaCampaign $campaign, array $context, int $runId): ?array
    {
        $threshold = config('recommendations.low_ctr_threshold', 0.5);
        $avgCtr = $context['averages']['avgCTR'];

        if ($avgCtr < $threshold) {
            Log::info('[PERFORMANCE_AGENT] LOW_CTR detected', [
                'campaign_id' => $campaign->id,
                'avg_ctr' => $avgCtr,
                'threshold' => $threshold,
            ]);

            return $this->factory->createPerformance(
                type: RecommendationTypeEnum::LOW_CTR,
                severity: RecommendationSeverityEnum::MEDIUM,
                title: 'Low click-through rate detected',
                explanation: "Campaign '{$campaign->name}' has an average CTR of " . round($avgCtr, 2) . "% over the last {$context['insights']['daysAnalyzed']} days, which is below the threshold of {$threshold}%. This suggests the ad creative or targeting may not be resonating with the audience.",
                proposedAction: 'Review and refresh ad creative, refine audience targeting, or test different ad formats and messaging.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'avg_ctr' => $avgCtr,
                    'threshold' => $threshold,
                    'total_impressions' => $context['aggregated']['totalImpressions'],
                    'total_clicks' => $context['aggregated']['totalClicks'],
                ]
            );
        }

        return null;
    }

    /**
     * Check for high CPC.
     */
    private function checkHighCpc(MetaCampaign $campaign, array $context, int $runId): ?array
    {
        $threshold = config('recommendations.high_cpc_threshold', 2.0);
        $avgCpc = $context['averages']['avgCPC'];

        if ($avgCpc > $threshold) {
            Log::info('[PERFORMANCE_AGENT] HIGH_CPC detected', [
                'campaign_id' => $campaign->id,
                'avg_cpc' => $avgCpc,
                'threshold' => $threshold,
            ]);

            return $this->factory->createPerformance(
                type: RecommendationTypeEnum::HIGH_CPC,
                severity: RecommendationSeverityEnum::MEDIUM,
                title: 'High cost per click detected',
                explanation: "Campaign '{$campaign->name}' has an average CPC of \$" . round($avgCpc, 2) . " over the last {$context['insights']['daysAnalyzed']} days, which exceeds the threshold of \${$threshold}. High CPC indicates inefficient spending or competitive bidding.",
                proposedAction: 'Optimize audience targeting to reduce competition, adjust bid strategy, or improve ad relevance score.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'avg_cpc' => $avgCpc,
                    'threshold' => $threshold,
                    'total_spend' => $context['aggregated']['totalSpend'],
                    'total_clicks' => $context['aggregated']['totalClicks'],
                ]
            );
        }

        return null;
    }

    /**
     * Check for low ROAS.
     */
    private function checkLowRoas(MetaCampaign $campaign, array $context, int $runId): ?array
    {
        $threshold = config('recommendations.low_roas_threshold', 1.0);
        $avgRoas = $context['averages']['avgROAS'];

        // Only check if there are purchases
        if ($context['aggregated']['totalPurchases'] > 0 && $avgRoas < $threshold) {
            Log::info('[PERFORMANCE_AGENT] LOW_ROAS detected', [
                'campaign_id' => $campaign->id,
                'avg_roas' => $avgRoas,
                'threshold' => $threshold,
            ]);

            return $this->factory->createPerformance(
                type: RecommendationTypeEnum::LOW_ROAS,
                severity: RecommendationSeverityEnum::HIGH,
                title: 'Low return on ad spend detected',
                explanation: "Campaign '{$campaign->name}' has an average ROAS of " . round($avgRoas, 2) . "x over the last {$context['insights']['daysAnalyzed']} days, which is below the threshold of {$threshold}x. This means you're spending more than you're earning back.",
                proposedAction: 'Review conversion funnel, optimize landing pages, adjust targeting to higher-intent audiences, or consider pausing the campaign.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'avg_roas' => $avgRoas,
                    'threshold' => $threshold,
                    'total_spend' => $context['aggregated']['totalSpend'],
                    'total_revenue' => $context['aggregated']['totalPurchaseValue'],
                ]
            );
        }

        return null;
    }

    /**
     * Check for high frequency.
     */
    private function checkHighFrequency(MetaCampaign $campaign, array $context, int $runId): ?array
    {
        $threshold = config('recommendations.high_frequency_threshold', 3.0);
        $avgFrequency = $context['averages']['avgFrequency'];

        if ($avgFrequency > $threshold) {
            Log::info('[PERFORMANCE_AGENT] HIGH_FREQUENCY detected', [
                'campaign_id' => $campaign->id,
                'avg_frequency' => $avgFrequency,
                'threshold' => $threshold,
            ]);

            return $this->factory->createPerformance(
                type: RecommendationTypeEnum::HIGH_FREQUENCY,
                severity: RecommendationSeverityEnum::MEDIUM,
                title: 'High frequency indicates ad fatigue',
                explanation: "Campaign '{$campaign->name}' has an average frequency of " . round($avgFrequency, 2) . " over the last {$context['insights']['daysAnalyzed']} days, exceeding the threshold of {$threshold}. High frequency means users are seeing the same ads repeatedly, which can lead to diminishing returns.",
                proposedAction: 'Expand audience size, rotate creative assets, or adjust frequency caps to reduce ad fatigue.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'avg_frequency' => $avgFrequency,
                    'threshold' => $threshold,
                    'total_reach' => $context['aggregated']['totalReach'],
                ]
            );
        }

        return null;
    }

    /**
     * Check for spend without purchases.
     */
    private function checkSpendWithoutPurchases(MetaCampaign $campaign, array $context, int $runId): ?array
    {
        $minSpend = config('recommendations.min_spend_for_serious_evaluation', 50);
        $totalSpend = $context['aggregated']['totalSpend'];
        $totalPurchases = $context['aggregated']['totalPurchases'];

        if ($totalSpend >= $minSpend && $totalPurchases === 0) {
            Log::info('[PERFORMANCE_AGENT] SPEND_WITHOUT_PURCHASES detected', [
                'campaign_id' => $campaign->id,
                'total_spend' => $totalSpend,
            ]);

            return $this->factory->createPerformance(
                type: RecommendationTypeEnum::SPEND_WITHOUT_PURCHASES,
                severity: RecommendationSeverityEnum::CRITICAL,
                title: 'Campaign spending without generating purchases',
                explanation: "Campaign '{$campaign->name}' has spent \${$totalSpend} over the last {$context['insights']['daysAnalyzed']} days but has not generated any purchases. This indicates a critical issue with conversion tracking, targeting, or offer quality.",
                proposedAction: 'Verify conversion tracking is properly implemented, review targeting strategy, analyze landing page performance, and consider pausing the campaign until issues are resolved.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'total_spend' => $totalSpend,
                    'total_clicks' => $context['aggregated']['totalClicks'],
                    'total_impressions' => $context['aggregated']['totalImpressions'],
                ]
            );
        }

        return null;
    }

    /**
     * Check for budget underutilization.
     */
    private function checkBudgetUnderutilized(MetaCampaign $campaign, array $context, int $runId, int $days): ?array
    {
        if (!$campaign->daily_budget) {
            return null;
        }

        $threshold = config('recommendations.budget_underutilized_ratio', 0.3);
        $expectedSpend = $campaign->daily_budget * $days;
        $actualSpend = $context['aggregated']['totalSpend'];
        $utilizationRatio = $expectedSpend > 0 ? $actualSpend / $expectedSpend : 0;

        if ($utilizationRatio < $threshold) {
            Log::info('[PERFORMANCE_AGENT] BUDGET_UNDERUTILIZED detected', [
                'campaign_id' => $campaign->id,
                'utilization_ratio' => $utilizationRatio,
                'threshold' => $threshold,
            ]);

            return $this->factory->createPerformance(
                type: RecommendationTypeEnum::BUDGET_UNDERUTILIZED,
                severity: RecommendationSeverityEnum::LOW,
                title: 'Campaign not spending allocated budget',
                explanation: "Campaign '{$campaign->name}' has a daily budget of \${$campaign->daily_budget} but has only spent \${$actualSpend} over {$days} days (expected \${$expectedSpend}). Utilization rate is " . round($utilizationRatio * 100, 1) . "%, below the {$threshold}% threshold.",
                proposedAction: 'Review bid strategy, expand audience targeting, increase bids, or reduce budget to match actual spending.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'daily_budget' => $campaign->daily_budget,
                    'expected_spend' => $expectedSpend,
                    'actual_spend' => $actualSpend,
                    'utilization_ratio' => $utilizationRatio,
                ]
            );
        }

        return null;
    }

    /**
     * Check for scale winner opportunity.
     */
    private function checkScaleWinner(MetaCampaign $campaign, array $context, int $runId): ?array
    {
        $minRoas = config('recommendations.scale_winner_min_roas', 3.0);
        $minPurchases = config('recommendations.scale_winner_min_purchases', 10);
        $avgRoas = $context['averages']['avgROAS'];
        $totalPurchases = $context['aggregated']['totalPurchases'];

        if ($avgRoas >= $minRoas && $totalPurchases >= $minPurchases) {
            Log::info('[PERFORMANCE_AGENT] SCALE_WINNER detected', [
                'campaign_id' => $campaign->id,
                'avg_roas' => $avgRoas,
                'total_purchases' => $totalPurchases,
            ]);

            return $this->factory->createPerformance(
                type: RecommendationTypeEnum::SCALE_WINNER,
                severity: RecommendationSeverityEnum::LOW,
                title: 'High-performing campaign ready to scale',
                explanation: "Campaign '{$campaign->name}' is performing exceptionally well with an average ROAS of " . round($avgRoas, 2) . "x and {$totalPurchases} purchases over the last {$context['insights']['daysAnalyzed']} days. This is a strong candidate for scaling.",
                proposedAction: 'Consider increasing budget gradually (10-20% at a time) to scale results while maintaining efficiency. Monitor performance closely during scaling.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'avg_roas' => $avgRoas,
                    'total_purchases' => $totalPurchases,
                    'total_spend' => $context['aggregated']['totalSpend'],
                    'current_daily_budget' => $campaign->daily_budget,
                    'recommended_budget_increase' => $campaign->daily_budget ? $campaign->daily_budget * 1.2 : null,
                ]
            );
        }

        return null;
    }

    /**
     * Check for pause loser recommendation.
     */
    private function checkPauseLoser(MetaCampaign $campaign, array $context, int $runId): ?array
    {
        $minSpend = config('recommendations.pause_loser_min_spend', 100);
        $maxRoas = config('recommendations.pause_loser_max_roas', 0.5);
        $totalSpend = $context['aggregated']['totalSpend'];
        $avgRoas = $context['averages']['avgROAS'];

        // Only check if there are purchases (otherwise caught by SPEND_WITHOUT_PURCHASES)
        if ($totalSpend >= $minSpend && $context['aggregated']['totalPurchases'] > 0 && $avgRoas < $maxRoas) {
            Log::info('[PERFORMANCE_AGENT] PAUSE_LOSER detected', [
                'campaign_id' => $campaign->id,
                'total_spend' => $totalSpend,
                'avg_roas' => $avgRoas,
            ]);

            return $this->factory->createPerformance(
                type: RecommendationTypeEnum::PAUSE_LOSER,
                severity: RecommendationSeverityEnum::HIGH,
                title: 'Consistently underperforming campaign',
                explanation: "Campaign '{$campaign->name}' has spent \${$totalSpend} with an average ROAS of only " . round($avgRoas, 2) . "x over the last {$context['insights']['daysAnalyzed']} days, well below the {$maxRoas}x threshold. Continuing to run this campaign is not cost-effective.",
                proposedAction: 'Pause this campaign to prevent further losses. Analyze what went wrong and either fix fundamental issues or reallocate budget to better-performing campaigns.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'total_spend' => $totalSpend,
                    'avg_roas' => $avgRoas,
                    'total_revenue' => $context['aggregated']['totalPurchaseValue'],
                    'net_loss' => $totalSpend - $context['aggregated']['totalPurchaseValue'],
                ]
            );
        }

        return null;
    }

    /**
     * Check for creative fatigue.
     */
    private function checkCreativeFatigue(MetaCampaign $campaign, array $context, int $runId): ?array
    {
        $threshold = config('recommendations.creative_fatigue_frequency_threshold', 4.0);
        $avgFrequency = $context['averages']['avgFrequency'];
        $avgCtr = $context['averages']['avgCTR'];
        $lowCtrThreshold = config('recommendations.low_ctr_threshold', 0.5);

        // Creative fatigue: high frequency combined with low CTR
        if ($avgFrequency > $threshold && $avgCtr < $lowCtrThreshold) {
            Log::info('[PERFORMANCE_AGENT] CREATIVE_FATIGUE detected', [
                'campaign_id' => $campaign->id,
                'avg_frequency' => $avgFrequency,
                'avg_ctr' => $avgCtr,
            ]);

            return $this->factory->createPerformance(
                type: RecommendationTypeEnum::CREATIVE_FATIGUE,
                severity: RecommendationSeverityEnum::MEDIUM,
                title: 'Creative fatigue detected',
                explanation: "Campaign '{$campaign->name}' shows signs of creative fatigue with high frequency (" . round($avgFrequency, 2) . ") and declining CTR (" . round($avgCtr, 2) . "%). Users have seen the ads too many times and are no longer engaging.",
                proposedAction: 'Refresh ad creative with new images, videos, or copy. Consider rotating multiple creative variations or expanding the audience to reduce frequency.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'avg_frequency' => $avgFrequency,
                    'avg_ctr' => $avgCtr,
                    'total_reach' => $context['aggregated']['totalReach'],
                ]
            );
        }

        return null;
    }
}
