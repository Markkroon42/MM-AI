<?php

namespace App\Services\AI;

use App\Enums\AiAgentTypeEnum;
use App\Enums\DraftEnrichmentTypeEnum;
use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;
use App\Models\DraftEnrichment;
use App\Services\AI\PromptBuilders\CopyPromptBuilder;
use Illuminate\Support\Facades\Log;

class CopyAgentService
{
    protected LlmGateway $llmGateway;
    protected PromptConfigResolver $configResolver;
    protected AiUsageLogger $usageLogger;
    protected CopyPromptBuilder $promptBuilder;
    protected DraftEnrichmentService $enrichmentService;

    public function __construct(
        LlmGateway $llmGateway,
        PromptConfigResolver $configResolver,
        AiUsageLogger $usageLogger,
        CopyPromptBuilder $promptBuilder,
        DraftEnrichmentService $enrichmentService
    ) {
        $this->llmGateway = $llmGateway;
        $this->configResolver = $configResolver;
        $this->usageLogger = $usageLogger;
        $this->promptBuilder = $promptBuilder;
        $this->enrichmentService = $enrichmentService;
    }

    /**
     * Generate copy variants for a campaign briefing
     *
     * @param CampaignBriefing $briefing
     * @return DraftEnrichment
     */
    public function generateForBriefing(CampaignBriefing $briefing): DraftEnrichment
    {
        Log::info('[COPY_AGENT] Generating copy for briefing', [
            'briefing_id' => $briefing->id,
        ]);

        try {
            // Resolve config
            $config = $this->configResolver->resolveByKey('copy_agent_default');

            // Build context
            $context = $this->promptBuilder->buildForBriefing($briefing);

            // Start usage log
            $usageLog = $this->usageLogger->start(
                'CopyAgent',
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
                DraftEnrichmentTypeEnum::COPY_VARIANTS,
                $result['data'],
                $usageLog,
                auth()->user()
            );

            Log::info('[COPY_AGENT] Copy generation successful', [
                'enrichment_id' => $enrichment->id,
            ]);

            return $enrichment;

        } catch (\Exception $e) {
            Log::error('[COPY_AGENT] Copy generation failed', [
                'error' => $e->getMessage(),
            ]);

            if (isset($usageLog)) {
                $this->usageLogger->markFailed($usageLog, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Generate copy variants for an existing draft
     * Fix #5: Prevent duplicate generation
     *
     * @param CampaignDraft $draft
     * @return DraftEnrichment
     */
    public function generateForDraft(CampaignDraft $draft): DraftEnrichment
    {
        Log::info('[COPY_AGENT] Generating copy for draft', [
            'draft_id' => $draft->id,
        ]);

        // Fix #5: Check for recent enrichment to prevent duplicates
        $recentEnrichment = $draft->draftEnrichments()
            ->where('enrichment_type', DraftEnrichmentTypeEnum::COPY_VARIANTS->value)
            ->where('created_at', '>', now()->subMinutes(3))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentEnrichment) {
            Log::info('[COPY_AGENT] Skipping duplicate generation - recent enrichment exists', [
                'draft_id' => $draft->id,
                'enrichment_id' => $recentEnrichment->id,
                'enrichment_age_seconds' => now()->diffInSeconds($recentEnrichment->created_at),
            ]);
            return $recentEnrichment;
        }

        try {
            // Resolve config
            $config = $this->configResolver->resolveByKey('copy_agent_default');

            // Build context
            $context = $this->promptBuilder->buildForDraft($draft);

            // Start usage log
            $usageLog = $this->usageLogger->start(
                'CopyAgent',
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
                DraftEnrichmentTypeEnum::COPY_VARIANTS,
                $result['data'],
                $usageLog,
                auth()->user()
            );

            Log::info('[COPY_AGENT] Copy generation successful', [
                'enrichment_id' => $enrichment->id,
            ]);

            // Fix Issue #1: Automatically apply copy enrichment to draft ads
            Log::info('[COPY_AGENT] Auto-applying copy enrichment to draft', [
                'enrichment_id' => $enrichment->id,
                'draft_id' => $draft->id,
            ]);

            $this->enrichmentService->applyEnrichment($enrichment, auth()->user());

            Log::info('[COPY_AGENT] Copy auto-apply completed', [
                'enrichment_id' => $enrichment->id,
            ]);

            return $enrichment;

        } catch (\Exception $e) {
            Log::error('[COPY_AGENT] Copy generation failed', [
                'error' => $e->getMessage(),
            ]);

            if (isset($usageLog)) {
                $this->usageLogger->markFailed($usageLog, $e->getMessage());
            }

            throw $e;
        }
    }
}
