<?php

namespace App\Services\Execution;

use App\Enums\PublishActionTypeEnum;
use App\Enums\RecommendationStatusEnum;
use App\Enums\RecommendationTypeEnum;
use App\Models\AuditLog;
use App\Models\CampaignRecommendation;
use App\Services\Guardrails\GuardrailContextBuilder;
use App\Services\Guardrails\GuardrailEngine;
use Illuminate\Support\Facades\Log;

class RecommendationExecutionService
{
    public function __construct(
        protected PublishJobService $publishJobService,
        protected GuardrailEngine $guardrailEngine,
        protected GuardrailContextBuilder $contextBuilder
    ) {}

    /**
     * Check if recommendation can be executed
     */
    public function canExecute(CampaignRecommendation $recommendation): bool
    {
        if ($recommendation->status !== RecommendationStatusEnum::APPROVED->value) {
            return false;
        }

        if (empty($recommendation->action_payload_json)) {
            return false;
        }

        return true;
    }

    /**
     * Execute approved recommendation
     */
    public function execute(CampaignRecommendation $recommendation): void
    {
        if (!$this->canExecute($recommendation)) {
            Log::warning('[RECOMMENDATION_EXECUTION] Cannot execute recommendation', [
                'recommendation_id' => $recommendation->id,
                'status' => $recommendation->status,
                'has_payload' => !empty($recommendation->action_payload_json),
            ]);

            throw new \Exception('Recommendation cannot be executed. It must be approved and have action payload.');
        }

        Log::info('[RECOMMENDATION_EXECUTION] Executing recommendation', [
            'recommendation_id' => $recommendation->id,
            'recommendation_type' => $recommendation->recommendation_type,
        ]);

        AuditLog::log(
            'recommendation_execution_started',
            $recommendation,
            null,
            null,
            ['recommendation_type' => $recommendation->recommendation_type]
        );

        try {
            // Check guardrails before execution
            if (config('guardrails.enabled', true)) {
                $context = $this->contextBuilder->buildRecommendationExecutionContext($recommendation);
                $decision = $this->guardrailEngine->evaluate('recommendation_execution', $context);

                if ($decision->isBlocked()) {
                    Log::warning('[RECOMMENDATION_EXECUTION] Blocked by guardrails', [
                        'recommendation_id' => $recommendation->id,
                        'reason' => $decision->message,
                    ]);

                    throw new \Exception("Execution blocked by guardrails: {$decision->message}");
                }

                if ($decision->requiresApproval()) {
                    Log::info('[RECOMMENDATION_EXECUTION] Requires approval', [
                        'recommendation_id' => $recommendation->id,
                        'reason' => $decision->message,
                    ]);

                    // Note: In a full implementation, we'd create an Approval record here
                    throw new \Exception("Execution requires approval: {$decision->message}");
                }

                if ($decision->hasWarning()) {
                    Log::warning('[RECOMMENDATION_EXECUTION] Guardrail warning', [
                        'recommendation_id' => $recommendation->id,
                        'warning' => $decision->message,
                    ]);
                }
            }

            $actionType = $this->mapRecommendationTypeToActionType($recommendation->recommendation_type);

            // Create publish job for execution
            $publishJob = $this->publishJobService->create(
                draft: null, // Recommendations don't have drafts
                actionType: $actionType,
                payload: array_merge(
                    $recommendation->action_payload_json,
                    [
                        'recommendation_id' => $recommendation->id,
                        'meta_campaign_id' => $recommendation->meta_campaign_id,
                        'meta_ad_set_id' => $recommendation->meta_ad_set_id,
                        'meta_ad_id' => $recommendation->meta_ad_id,
                    ]
                )
            );

            // Update recommendation status
            $recommendation->update([
                'status' => RecommendationStatusEnum::EXECUTED->value,
                'executed_at' => now(),
            ]);

            AuditLog::log(
                'recommendation_execution_success',
                $recommendation,
                ['status' => RecommendationStatusEnum::APPROVED->value],
                ['status' => RecommendationStatusEnum::EXECUTED->value],
                ['publish_job_id' => $publishJob->id]
            );

            Log::info('[RECOMMENDATION_EXECUTION] Recommendation executed successfully', [
                'recommendation_id' => $recommendation->id,
                'publish_job_id' => $publishJob->id,
            ]);
        } catch (\Exception $e) {
            AuditLog::log(
                'recommendation_execution_failed',
                $recommendation,
                null,
                null,
                ['error' => $e->getMessage()]
            );

            Log::error('[RECOMMENDATION_EXECUTION] Recommendation execution failed', [
                'recommendation_id' => $recommendation->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Map recommendation type to publish action type
     */
    protected function mapRecommendationTypeToActionType(string $recommendationType): PublishActionTypeEnum
    {
        return match ($recommendationType) {
            RecommendationTypeEnum::PAUSE_LOSER->value => PublishActionTypeEnum::PAUSE_CAMPAIGN,
            RecommendationTypeEnum::SCALE_WINNER->value => PublishActionTypeEnum::UPDATE_CAMPAIGN_BUDGET,
            default => throw new \Exception("Unsupported recommendation type: {$recommendationType}"),
        };
    }
}
