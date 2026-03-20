<?php

namespace App\Services\AI;

use App\Enums\DraftEnrichmentStatusEnum;
use App\Enums\DraftEnrichmentTypeEnum;
use App\Models\AiUsageLog;
use App\Models\CampaignDraft;
use App\Models\DraftEnrichment;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DraftEnrichmentService
{
    /**
     * Store a new enrichment
     *
     * @param CampaignDraft $draft
     * @param DraftEnrichmentTypeEnum $type
     * @param array $payload
     * @param AiUsageLog|null $log
     * @param User|null $user
     * @return DraftEnrichment
     */
    public function storeEnrichment(
        CampaignDraft $draft,
        DraftEnrichmentTypeEnum $type,
        array $payload,
        ?AiUsageLog $log = null,
        ?User $user = null
    ): DraftEnrichment {
        Log::info('[DRAFT_ENRICHMENT] Storing enrichment', [
            'draft_id' => $draft->id,
            'type' => $type->value,
        ]);

        $enrichment = DraftEnrichment::create([
            'campaign_draft_id' => $draft->id,
            'ai_usage_log_id' => $log?->id,
            'enrichment_type' => $type->value,
            'status' => DraftEnrichmentStatusEnum::DRAFT->value,
            'payload_json' => $payload,
            'created_by' => $user?->id,
        ]);

        return $enrichment;
    }

    /**
     * Approve an enrichment
     *
     * @param DraftEnrichment $enrichment
     * @param User $user
     * @return void
     */
    public function approveEnrichment(DraftEnrichment $enrichment, User $user): void
    {
        Log::info('[DRAFT_ENRICHMENT] Approving enrichment', [
            'enrichment_id' => $enrichment->id,
            'user_id' => $user->id,
        ]);

        $enrichment->update([
            'status' => DraftEnrichmentStatusEnum::APPROVED->value,
        ]);
    }

    /**
     * Reject an enrichment
     *
     * @param DraftEnrichment $enrichment
     * @param User $user
     * @return void
     */
    public function rejectEnrichment(DraftEnrichment $enrichment, User $user): void
    {
        Log::info('[DRAFT_ENRICHMENT] Rejecting enrichment', [
            'enrichment_id' => $enrichment->id,
            'user_id' => $user->id,
        ]);

        $enrichment->update([
            'status' => DraftEnrichmentStatusEnum::REJECTED->value,
        ]);
    }

    /**
     * Apply an enrichment to the draft
     * Safely merges enrichment into draft without overwriting existing data
     *
     * @param DraftEnrichment $enrichment
     * @param User $user
     * @return void
     */
    public function applyEnrichment(DraftEnrichment $enrichment, User $user): void
    {
        Log::info('[DRAFT_ENRICHMENT] Applying enrichment', [
            'enrichment_id' => $enrichment->id,
            'user_id' => $user->id,
        ]);

        $draft = $enrichment->campaignDraft;
        $currentPayload = $draft->draft_payload_json ?? [];

        // Initialize ai_enrichments section if it doesn't exist
        if (!isset($currentPayload['ai_enrichments'])) {
            $currentPayload['ai_enrichments'] = [];
        }

        // Map enrichment type to payload key
        $enrichmentKey = match($enrichment->enrichment_type) {
            DraftEnrichmentTypeEnum::COPY_VARIANTS->value => 'copy_variants',
            DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value => 'creative_suggestions',
            DraftEnrichmentTypeEnum::STRATEGY_NOTES->value => 'strategy_notes',
            DraftEnrichmentTypeEnum::FULL_ENRICHMENT->value => 'full_enrichment',
            default => 'other',
        };

        // Add enrichment to ai_enrichments section with metadata
        $currentPayload['ai_enrichments'][$enrichmentKey] = [
            'data' => $enrichment->payload_json,
            'applied_at' => now()->toIso8601String(),
            'applied_by' => $user->id,
            'enrichment_id' => $enrichment->id,
        ];

        // Update draft
        $draft->update([
            'draft_payload_json' => $currentPayload,
        ]);

        // Update enrichment status
        $enrichment->update([
            'status' => DraftEnrichmentStatusEnum::APPLIED->value,
        ]);

        Log::info('[DRAFT_ENRICHMENT] Enrichment applied successfully', [
            'enrichment_id' => $enrichment->id,
            'enrichment_key' => $enrichmentKey,
        ]);
    }
}
