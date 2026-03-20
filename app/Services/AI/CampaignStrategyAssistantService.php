<?php

namespace App\Services\AI;

use App\Enums\AiAgentTypeEnum;
use App\Models\BriefingStrategyNote;
use App\Models\CampaignBriefing;
use App\Services\AI\PromptBuilders\StrategyPromptBuilder;
use Illuminate\Support\Facades\Log;

class CampaignStrategyAssistantService
{
    protected LlmGateway $llmGateway;
    protected PromptConfigResolver $configResolver;
    protected AiUsageLogger $usageLogger;
    protected StrategyPromptBuilder $promptBuilder;

    public function __construct(
        LlmGateway $llmGateway,
        PromptConfigResolver $configResolver,
        AiUsageLogger $usageLogger,
        StrategyPromptBuilder $promptBuilder
    ) {
        $this->llmGateway = $llmGateway;
        $this->configResolver = $configResolver;
        $this->usageLogger = $usageLogger;
        $this->promptBuilder = $promptBuilder;
    }

    /**
     * Generate strategy recommendations for a campaign briefing
     *
     * @param CampaignBriefing $briefing
     * @return BriefingStrategyNote
     */
    public function generateForBriefing(CampaignBriefing $briefing): BriefingStrategyNote
    {
        Log::info('[STRATEGY_ASSISTANT] Generating strategy for briefing', [
            'briefing_id' => $briefing->id,
        ]);

        try {
            // Resolve config
            $config = $this->configResolver->resolveByKey('strategy_assistant_default');

            // Build context
            $context = $this->promptBuilder->buildForBriefing($briefing);

            // Start usage log
            $usageLog = $this->usageLogger->start(
                'StrategyAssistant',
                $config,
                $briefing,
                null,
                $context
            );

            // Generate via LLM
            $result = $this->llmGateway->generate(
                $config,
                $config->user_prompt_template,
                $context
            );

            if (!$result['success']) {
                throw new \Exception('LLM generation failed');
            }

            // Calculate cost
            $cost = $this->usageLogger->calculateCost(
                $config->model,
                $result['usage']['prompt_tokens'],
                $result['usage']['completion_tokens']
            );

            // Mark success
            $this->usageLogger->markSuccess(
                $usageLog,
                $result['data'],
                $result['usage']['prompt_tokens'],
                $result['usage']['completion_tokens'],
                $cost
            );

            // Create strategy note
            $strategyNote = BriefingStrategyNote::create([
                'campaign_briefing_id' => $briefing->id,
                'ai_usage_log_id' => $usageLog->id,
                'strategy_payload_json' => $result['data'],
            ]);

            Log::info('[STRATEGY_ASSISTANT] Strategy generation successful', [
                'strategy_note_id' => $strategyNote->id,
            ]);

            return $strategyNote;

        } catch (\Exception $e) {
            Log::error('[STRATEGY_ASSISTANT] Strategy generation failed', [
                'error' => $e->getMessage(),
            ]);

            if (isset($usageLog)) {
                $this->usageLogger->markFailed($usageLog, $e->getMessage());
            }

            throw $e;
        }
    }
}
