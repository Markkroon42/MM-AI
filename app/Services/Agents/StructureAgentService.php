<?php

namespace App\Services\Agents;

use App\Enums\AgentRunStatusEnum;
use App\Enums\AgentScopeTypeEnum;
use App\Enums\RecommendationSeverityEnum;
use App\Enums\RecommendationTypeEnum;
use App\Models\AgentRun;
use App\Models\MetaCampaign;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StructureAgentService
{
    public function __construct(
        private RecommendationFactory $factory,
    ) {}

    /**
     * Analyze campaign structure and return recommendations.
     *
     * @param MetaCampaign $campaign
     * @return array
     */
    public function analyzeCampaign(MetaCampaign $campaign): array
    {
        $recommendations = [];

        // Create agent run record
        $agentRun = AgentRun::create([
            'agent_name' => 'structure_agent',
            'scope_type' => AgentScopeTypeEnum::CAMPAIGN->value,
            'scope_id' => $campaign->id,
            'status' => AgentRunStatusEnum::RUNNING->value,
            'started_at' => Carbon::now(),
            'input_payload_json' => [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
            ],
        ]);

        Log::info('[STRUCTURE_AGENT] Starting analysis', [
            'run_id' => $agentRun->id,
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
        ]);

        try {
            // Load relationships
            $campaign->load(['adSets.ads', 'metaAdAccount']);

            // 1. Check for no ad sets
            $noAdSetsCheck = $this->checkNoAdSets($campaign, $agentRun->id);
            if ($noAdSetsCheck) {
                $recommendations[] = $noAdSetsCheck;
            }

            // 2. Check for ad sets with no ads
            $noAdsChecks = $this->checkNoAds($campaign, $agentRun->id);
            $recommendations = array_merge($recommendations, $noAdsChecks);

            // 3. Check naming convention
            $namingCheck = $this->checkNamingViolation($campaign, $agentRun->id);
            if ($namingCheck) {
                $recommendations[] = $namingCheck;
            }

            // 4. Check for missing UTM parameters
            $utmCheck = $this->checkMissingUtm($campaign, $agentRun->id);
            if ($utmCheck) {
                $recommendations[] = $utmCheck;
            }

            // 5. Check for duplicate structures
            $duplicateCheck = $this->checkDuplicateStructure($campaign, $agentRun->id);
            if ($duplicateCheck) {
                $recommendations[] = $duplicateCheck;
            }

            // 6. Check for inactive but spending campaigns
            $inactiveCheck = $this->checkInactiveButSpending($campaign, $agentRun->id);
            if ($inactiveCheck) {
                $recommendations[] = $inactiveCheck;
            }

            // Update agent run as successful
            $agentRun->update([
                'status' => AgentRunStatusEnum::SUCCESS->value,
                'finished_at' => Carbon::now(),
                'output_payload_json' => [
                    'recommendations_found' => count($recommendations),
                    'checks_performed' => 6,
                ],
            ]);

            Log::info('[STRUCTURE_AGENT] Analysis completed successfully', [
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

            Log::error('[STRUCTURE_AGENT] Analysis failed', [
                'run_id' => $agentRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $recommendations;
    }

    /**
     * Check if campaign has no ad sets.
     */
    private function checkNoAdSets(MetaCampaign $campaign, int $runId): ?array
    {
        if ($campaign->adSets->count() === 0) {
            Log::info('[STRUCTURE_AGENT] NO_AD_SETS detected', [
                'campaign_id' => $campaign->id,
            ]);

            return $this->factory->createStructure(
                type: RecommendationTypeEnum::NO_AD_SETS,
                severity: RecommendationSeverityEnum::HIGH,
                title: 'Campaign has no ad sets',
                explanation: "The campaign '{$campaign->name}' is active but has no ad sets configured. A campaign without ad sets cannot serve any ads or generate any results.",
                proposedAction: 'Create at least one ad set with proper targeting, budget, and optimization settings to start serving ads.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'campaign_id' => $campaign->id,
                    'recommended_action' => 'create_ad_set',
                ]
            );
        }

        return null;
    }

    /**
     * Check for ad sets with no ads.
     */
    private function checkNoAds(MetaCampaign $campaign, int $runId): array
    {
        $recommendations = [];

        foreach ($campaign->adSets as $adSet) {
            if ($adSet->ads->count() === 0) {
                Log::info('[STRUCTURE_AGENT] NO_ADS detected', [
                    'campaign_id' => $campaign->id,
                    'ad_set_id' => $adSet->id,
                ]);

                $recommendations[] = $this->factory->createStructure(
                    type: RecommendationTypeEnum::NO_ADS,
                    severity: RecommendationSeverityEnum::MEDIUM,
                    title: "Ad Set '{$adSet->name}' has no ads",
                    explanation: "The ad set '{$adSet->name}' in campaign '{$campaign->name}' has no ads configured. An ad set without ads cannot serve any impressions or generate clicks.",
                    proposedAction: 'Create at least one ad with creative assets for this ad set to start serving impressions.',
                    target: $adSet,
                    runId: $runId,
                    actionPayload: [
                        'ad_set_id' => $adSet->id,
                        'recommended_action' => 'create_ad',
                    ]
                );
            }
        }

        return $recommendations;
    }

    /**
     * Check if campaign name follows naming convention.
     */
    private function checkNamingViolation(MetaCampaign $campaign, int $runId): ?array
    {
        $requiredSegments = config('recommendations.naming_pattern_required_segments', 3);
        $segments = explode('_', $campaign->name);

        if (count($segments) < $requiredSegments) {
            Log::info('[STRUCTURE_AGENT] NAMING_VIOLATION detected', [
                'campaign_id' => $campaign->id,
                'segments_found' => count($segments),
                'required_segments' => $requiredSegments,
            ]);

            return $this->factory->createStructure(
                type: RecommendationTypeEnum::NAMING_VIOLATION,
                severity: RecommendationSeverityEnum::LOW,
                title: 'Campaign name does not follow naming convention',
                explanation: "The campaign '{$campaign->name}' does not follow the standard naming convention. Expected at least {$requiredSegments} underscore-separated segments, but found " . count($segments) . '. Proper naming helps with organization and reporting.',
                proposedAction: "Rename campaign to follow format: objective_audience_creative or similar structured format with at least {$requiredSegments} segments.",
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'current_name' => $campaign->name,
                    'segments_found' => count($segments),
                    'required_segments' => $requiredSegments,
                ]
            );
        }

        return null;
    }

    /**
     * Check if campaign name has UTM-like tracking patterns.
     */
    private function checkMissingUtm(MetaCampaign $campaign, int $runId): ?array
    {
        $name = strtolower($campaign->name);

        // Basic check for common tracking patterns
        $hasTracking = str_contains($name, 'utm') ||
                       str_contains($name, 'source') ||
                       str_contains($name, 'medium') ||
                       str_contains($name, 'campaign');

        if (!$hasTracking) {
            Log::info('[STRUCTURE_AGENT] MISSING_UTM detected', [
                'campaign_id' => $campaign->id,
            ]);

            return $this->factory->createStructure(
                type: RecommendationTypeEnum::MISSING_UTM,
                severity: RecommendationSeverityEnum::MEDIUM,
                title: 'Campaign name lacks tracking identifiers',
                explanation: "The campaign '{$campaign->name}' does not appear to have tracking-related identifiers in its name. Including tracking information helps with attribution and analytics.",
                proposedAction: 'Consider including tracking parameters in the campaign name or ensure UTM parameters are properly set in ad URLs.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'current_name' => $campaign->name,
                ]
            );
        }

        return null;
    }

    /**
     * Check for duplicate or very similar campaign structures.
     */
    private function checkDuplicateStructure(MetaCampaign $campaign, int $runId): ?array
    {
        // Find campaigns with similar names in the same account
        $similarCampaigns = MetaCampaign::where('meta_ad_account_id', $campaign->meta_ad_account_id)
            ->where('id', '!=', $campaign->id)
            ->get()
            ->filter(function ($otherCampaign) use ($campaign) {
                similar_text(
                    strtolower($campaign->name),
                    strtolower($otherCampaign->name),
                    $percent
                );
                return $percent > 70;
            });

        if ($similarCampaigns->count() > 0) {
            $similarNames = $similarCampaigns->pluck('name')->take(3)->implode(', ');

            Log::info('[STRUCTURE_AGENT] DUPLICATE_STRUCTURE detected', [
                'campaign_id' => $campaign->id,
                'similar_count' => $similarCampaigns->count(),
            ]);

            return $this->factory->createStructure(
                type: RecommendationTypeEnum::DUPLICATE_STRUCTURE,
                severity: RecommendationSeverityEnum::MEDIUM,
                title: 'Similar campaigns detected in account',
                explanation: "Found {$similarCampaigns->count()} campaign(s) with similar names to '{$campaign->name}': {$similarNames}. Duplicate or very similar campaigns can lead to audience overlap and inefficient spending.",
                proposedAction: 'Review these campaigns for potential consolidation or ensure they target distinct audiences.',
                target: $campaign,
                runId: $runId,
                actionPayload: [
                    'similar_campaigns' => $similarCampaigns->map(fn($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                    ])->toArray(),
                ]
            );
        }

        return null;
    }

    /**
     * Check if campaign is inactive but still spending.
     */
    private function checkInactiveButSpending(MetaCampaign $campaign, int $runId): ?array
    {
        $inactiveStatuses = ['paused', 'deleted', 'archived'];

        if (in_array(strtolower($campaign->status ?? ''), $inactiveStatuses)) {
            // Check for recent spend
            $recentSpend = $campaign->insights()
                ->where('insight_date', '>=', Carbon::now()->subDays(3))
                ->sum('spend');

            if ($recentSpend > 0) {
                Log::info('[STRUCTURE_AGENT] INACTIVE_BUT_SPENDING detected', [
                    'campaign_id' => $campaign->id,
                    'status' => $campaign->status,
                    'recent_spend' => $recentSpend,
                ]);

                return $this->factory->createStructure(
                    type: RecommendationTypeEnum::INACTIVE_BUT_SPENDING,
                    severity: RecommendationSeverityEnum::HIGH,
                    title: 'Inactive campaign is still spending',
                    explanation: "Campaign '{$campaign->name}' has status '{$campaign->status}' but has spent \${$recentSpend} in the last 3 days. This indicates a potential issue with campaign status or reporting discrepancy.",
                    proposedAction: 'Verify campaign status in Meta Ads Manager and ensure it is properly paused if spending should stop.',
                    target: $campaign,
                    runId: $runId,
                    actionPayload: [
                        'status' => $campaign->status,
                        'recent_spend' => $recentSpend,
                    ]
                );
            }
        }

        return null;
    }
}
