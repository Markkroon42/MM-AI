<?php

namespace App\Services\Guardrails;

use App\Models\MetaCampaign;
use App\Models\CampaignRecommendation;

class GuardrailContextBuilder
{
    /**
     * Build context for budget increase action
     */
    public function buildBudgetIncreaseContext(array $payload): array
    {
        $campaignId = $payload['meta_campaign_id'] ?? null;
        $newBudget = $payload['new_daily_budget'] ?? 0;

        $context = [
            'action_type' => 'budget_increase',
            'new_daily_budget' => $newBudget,
            'budget_increase_percentage' => 0,
            'current_spend' => 0,
            'has_sufficient_data' => false,
        ];

        if ($campaignId) {
            $campaign = MetaCampaign::find($campaignId);
            if ($campaign) {
                $currentBudget = (float) $campaign->daily_budget;
                $context['current_daily_budget'] = $currentBudget;

                if ($currentBudget > 0) {
                    $context['budget_increase_percentage'] = (($newBudget - $currentBudget) / $currentBudget) * 100;
                }

                // Get recent spend data
                $recentSpend = $campaign->insights()
                    ->where('date', '>=', now()->subDays(7))
                    ->sum('spend');

                $context['current_spend'] = (float) $recentSpend;
                $context['has_sufficient_data'] = $recentSpend > 50; // Has spent more than €50
            }
        }

        return $context;
    }

    /**
     * Build context for campaign pause action
     */
    public function buildCampaignPauseContext(array $payload): array
    {
        $campaignId = $payload['meta_campaign_id'] ?? null;

        $context = [
            'action_type' => 'campaign_pause',
            'current_spend' => 0,
            'days_active' => 0,
            'has_conversions' => false,
        ];

        if ($campaignId) {
            $campaign = MetaCampaign::find($campaignId);
            if ($campaign) {
                // Get lifetime spend
                $totalSpend = $campaign->insights()->sum('spend');
                $context['current_spend'] = (float) $totalSpend;

                // Calculate days active
                if ($campaign->created_at) {
                    $context['days_active'] = now()->diffInDays($campaign->created_at);
                }

                // Check for conversions
                $conversions = $campaign->insights()->sum('conversions');
                $context['has_conversions'] = $conversions > 0;
            }
        }

        return $context;
    }

    /**
     * Build context for campaign publish action
     */
    public function buildCampaignPublishContext(array $payload): array
    {
        $dailyBudget = $payload['daily_budget'] ?? 0;

        return [
            'action_type' => 'campaign_publish',
            'daily_budget' => (float) $dailyBudget,
            'is_new_campaign' => true,
        ];
    }

    /**
     * Build context for recommendation execution
     */
    public function buildRecommendationExecutionContext(CampaignRecommendation $recommendation): array
    {
        $context = [
            'action_type' => 'recommendation_execution',
            'recommendation_type' => $recommendation->recommendation_type,
            'severity' => $recommendation->severity,
            'confidence_score' => (float) $recommendation->confidence_score,
        ];

        // Add specific context based on recommendation type
        if ($recommendation->action_payload_json) {
            if (isset($recommendation->action_payload_json['new_daily_budget'])) {
                $context = array_merge(
                    $context,
                    $this->buildBudgetIncreaseContext($recommendation->action_payload_json)
                );
            } elseif ($recommendation->recommendation_type === 'pause_loser') {
                $context = array_merge(
                    $context,
                    $this->buildCampaignPauseContext($recommendation->action_payload_json)
                );
            }
        }

        return $context;
    }

    /**
     * Build generic context from action type and payload
     */
    public function build(string $actionType, array $payload): array
    {
        return match ($actionType) {
            'budget_increase' => $this->buildBudgetIncreaseContext($payload),
            'campaign_pause' => $this->buildCampaignPauseContext($payload),
            'campaign_publish' => $this->buildCampaignPublishContext($payload),
            default => array_merge(['action_type' => $actionType], $payload),
        };
    }
}
