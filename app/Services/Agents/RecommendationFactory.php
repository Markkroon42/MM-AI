<?php

namespace App\Services\Agents;

use App\Enums\RecommendationSeverityEnum;
use App\Enums\RecommendationTypeEnum;
use App\Models\MetaAd;
use App\Models\MetaAdSet;
use App\Models\MetaCampaign;

class RecommendationFactory
{
    /**
     * Create a standardized recommendation array.
     *
     * @param RecommendationTypeEnum $type
     * @param RecommendationSeverityEnum $severity
     * @param string $title
     * @param string $explanation
     * @param string $proposedAction
     * @param string $sourceAgent
     * @param float $confidenceScore
     * @param MetaCampaign|MetaAdSet|MetaAd|null $target
     * @param int|null $runId
     * @param array|null $actionPayload
     * @return array
     */
    public function create(
        RecommendationTypeEnum $type,
        RecommendationSeverityEnum $severity,
        string $title,
        string $explanation,
        string $proposedAction,
        string $sourceAgent,
        float $confidenceScore,
        MetaCampaign|MetaAdSet|MetaAd|null $target = null,
        ?int $runId = null,
        ?array $actionPayload = null
    ): array {
        $recommendation = [
            'recommendation_type' => $type->value,
            'severity' => $severity->value,
            'title' => $title,
            'explanation' => $explanation,
            'proposed_action' => $proposedAction,
            'source_agent' => $sourceAgent,
            'confidence_score' => $confidenceScore,
            'created_by_run_id' => $runId,
            'action_payload_json' => $actionPayload,
        ];

        // Set target entity foreign keys
        if ($target instanceof MetaCampaign) {
            $recommendation['meta_campaign_id'] = $target->id;
        } elseif ($target instanceof MetaAdSet) {
            $recommendation['meta_ad_set_id'] = $target->id;
            $recommendation['meta_campaign_id'] = $target->meta_campaign_id;
        } elseif ($target instanceof MetaAd) {
            $recommendation['meta_ad_id'] = $target->id;
            $recommendation['meta_ad_set_id'] = $target->meta_ad_set_id;
        }

        return $recommendation;
    }

    /**
     * Create a performance-related recommendation.
     */
    public function createPerformance(
        RecommendationTypeEnum $type,
        RecommendationSeverityEnum $severity,
        string $title,
        string $explanation,
        string $proposedAction,
        MetaCampaign|MetaAdSet|MetaAd $target,
        ?int $runId = null,
        ?array $actionPayload = null
    ): array {
        return $this->create(
            type: $type,
            severity: $severity,
            title: $title,
            explanation: $explanation,
            proposedAction: $proposedAction,
            sourceAgent: 'performance_agent',
            confidenceScore: config('recommendations.confidence_scores.performance', 75.00),
            target: $target,
            runId: $runId,
            actionPayload: $actionPayload
        );
    }

    /**
     * Create a structure-related recommendation.
     */
    public function createStructure(
        RecommendationTypeEnum $type,
        RecommendationSeverityEnum $severity,
        string $title,
        string $explanation,
        string $proposedAction,
        MetaCampaign|MetaAdSet|MetaAd $target,
        ?int $runId = null,
        ?array $actionPayload = null
    ): array {
        return $this->create(
            type: $type,
            severity: $severity,
            title: $title,
            explanation: $explanation,
            proposedAction: $proposedAction,
            sourceAgent: 'structure_agent',
            confidenceScore: config('recommendations.confidence_scores.structure', 85.00),
            target: $target,
            runId: $runId,
            actionPayload: $actionPayload
        );
    }
}
