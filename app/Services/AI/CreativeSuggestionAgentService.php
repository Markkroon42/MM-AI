<?php

namespace App\Services\AI;

use App\Enums\AiAgentTypeEnum;
use App\Enums\DraftEnrichmentTypeEnum;
use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;
use App\Models\DraftEnrichment;
use App\Services\AI\PromptBuilders\CreativePromptBuilder;
use Illuminate\Support\Facades\Log;

class CreativeSuggestionAgentService
{
    protected LlmGateway $llmGateway;
    protected PromptConfigResolver $configResolver;
    protected AiUsageLogger $usageLogger;
    protected CreativePromptBuilder $promptBuilder;
    protected DraftEnrichmentService $enrichmentService;

    public function __construct(
        LlmGateway $llmGateway,
        PromptConfigResolver $configResolver,
        AiUsageLogger $usageLogger,
        CreativePromptBuilder $promptBuilder,
        DraftEnrichmentService $enrichmentService
    ) {
        $this->llmGateway = $llmGateway;
        $this->configResolver = $configResolver;
        $this->usageLogger = $usageLogger;
        $this->promptBuilder = $promptBuilder;
        $this->enrichmentService = $enrichmentService;
    }

    /**
     * Generate creative suggestions for a campaign briefing
     *
     * @param CampaignBriefing $briefing
     * @return DraftEnrichment
     */
    public function generateForBriefing(CampaignBriefing $briefing): DraftEnrichment
    {
        Log::info('[CREATIVE_AGENT] Generating creative suggestions for briefing', [
            'briefing_id' => $briefing->id,
        ]);

        try {
            // Resolve config
            $config = $this->configResolver->resolveByKey('creative_agent_default');

            // Build context
            $context = $this->promptBuilder->buildForBriefing($briefing);

            // Start usage log
            $usageLog = $this->usageLogger->start(
                'CreativeAgent',
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

            // Get or create draft
            $draft = $briefing->campaignDrafts()->first();
            if (!$draft) {
                $draft = $briefing->campaignDrafts()->create([
                    'briefing_id' => $briefing->id,
                    'generated_name' => 'Draft from ' . $briefing->brand,
                    'status' => 'DRAFT',
                    'draft_payload_json' => [],
                ]);
            }

            // Store enrichment
            $enrichment = $this->enrichmentService->storeEnrichment(
                $draft,
                DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS,
                $result['data'],
                $usageLog,
                auth()->user()
            );

            Log::info('[CREATIVE_AGENT] Creative generation successful', [
                'enrichment_id' => $enrichment->id,
            ]);

            return $enrichment;

        } catch (\Exception $e) {
            Log::error('[CREATIVE_AGENT] Creative generation failed', [
                'error' => $e->getMessage(),
            ]);

            if (isset($usageLog)) {
                $this->usageLogger->markFailed($usageLog, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Generate creative suggestions for an existing draft
     * Fix #5: Prevent duplicate generation
     *
     * @param CampaignDraft $draft
     * @return DraftEnrichment
     */
    public function generateForDraft(CampaignDraft $draft): DraftEnrichment
    {
        Log::info('[CREATIVE_AGENT] Generating creative suggestions for draft', [
            'draft_id' => $draft->id,
        ]);

        // Fix #5: Check for recent enrichment to prevent duplicates
        $recentEnrichment = $draft->draftEnrichments()
            ->where('enrichment_type', DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value)
            ->where('created_at', '>', now()->subMinutes(3))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentEnrichment) {
            Log::info('[CREATIVE_AGENT] Skipping duplicate generation - recent enrichment exists', [
                'draft_id' => $draft->id,
                'enrichment_id' => $recentEnrichment->id,
                'enrichment_age_seconds' => now()->diffInSeconds($recentEnrichment->created_at),
            ]);
            return $recentEnrichment;
        }

        try {
            // Resolve config
            $config = $this->configResolver->resolveByKey('creative_agent_default');

            // Build context
            $context = $this->promptBuilder->buildForDraft($draft);

            // Start usage log
            $usageLog = $this->usageLogger->start(
                'CreativeAgent',
                $config,
                $draft,
                $draft,
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

            // Store enrichment
            $enrichment = $this->enrichmentService->storeEnrichment(
                $draft,
                DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS,
                $result['data'],
                $usageLog,
                auth()->user()
            );

            Log::info('[CREATIVE_AGENT] Creative generation successful', [
                'enrichment_id' => $enrichment->id,
            ]);

            return $enrichment;

        } catch (\Exception $e) {
            Log::error('[CREATIVE_AGENT] Creative generation failed', [
                'error' => $e->getMessage(),
            ]);

            if (isset($usageLog)) {
                $this->usageLogger->markFailed($usageLog, $e->getMessage());
            }

            throw $e;
        }
    }
}
