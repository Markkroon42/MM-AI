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
        Log::info('[DRAFT_ENRICHMENT_APPLY] Starting enrichment application', [
            'enrichment_id' => $enrichment->id,
            'enrichment_type' => $enrichment->enrichment_type,
            'user_id' => $user->id,
            'draft_id' => $enrichment->campaign_draft_id,
        ]);

        $draft = $enrichment->campaignDraft;
        $currentPayload = $draft->draft_payload_json ?? [];

        // Initialize ai_enrichments section if it doesn't exist
        if (!isset($currentPayload['ai_enrichments'])) {
            $currentPayload['ai_enrichments'] = [];
            Log::info('[DRAFT_ENRICHMENT_APPLY] Initialized ai_enrichments section');
        }

        // Map enrichment type to payload key
        $enrichmentKey = match($enrichment->enrichment_type) {
            DraftEnrichmentTypeEnum::COPY_VARIANTS->value => 'copy_variants',
            DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value => 'creative_suggestions',
            DraftEnrichmentTypeEnum::STRATEGY_NOTES->value => 'strategy_notes',
            DraftEnrichmentTypeEnum::FULL_ENRICHMENT->value => 'full_enrichment',
            default => 'other',
        };

        Log::info('[DRAFT_ENRICHMENT_APPLY] Mapped enrichment type to key', [
            'enrichment_type' => $enrichment->enrichment_type,
            'enrichment_key' => $enrichmentKey,
        ]);

        // Add enrichment to ai_enrichments section with metadata
        $currentPayload['ai_enrichments'][$enrichmentKey] = [
            'data' => $enrichment->payload_json,
            'applied_at' => now()->toIso8601String(),
            'applied_by' => $user->id,
            'enrichment_id' => $enrichment->id,
        ];

        // Fix #2: Apply copy variants to actual ad payload
        if ($enrichment->enrichment_type === DraftEnrichmentTypeEnum::COPY_VARIANTS->value) {
            Log::info('[DRAFT_ENRICHMENT_APPLY] Applying copy variants to ads');
            $currentPayload = $this->applyCopyToAds($currentPayload, $enrichment->payload_json);
        }

        // Creative suggestions - store for reference
        if ($enrichment->enrichment_type === DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value) {
            Log::info('[DRAFT_ENRICHMENT_APPLY] Creative suggestion applied to ai_enrichments section', [
                'has_suggestions' => isset($enrichment->payload_json['suggestions']),
                'suggestion_count' => isset($enrichment->payload_json['suggestions'])
                    ? count($enrichment->payload_json['suggestions'])
                    : 0,
            ]);
        }

        // Update draft
        $draft->update([
            'draft_payload_json' => $currentPayload,
        ]);

        Log::info('[DRAFT_ENRICHMENT_APPLY] Draft payload updated');

        // Update enrichment status
        $enrichment->update([
            'status' => DraftEnrichmentStatusEnum::APPLIED->value,
        ]);

        Log::info('[DRAFT_ENRICHMENT_APPLY] Enrichment applied successfully', [
            'enrichment_id' => $enrichment->id,
            'enrichment_key' => $enrichmentKey,
            'enrichment_type' => $enrichment->enrichment_type,
        ]);
    }

    /**
     * Apply copy variants to ads in payload
     * Fix #2: Map copy enrichment to actual ad creative structure
     *
     * @param array $payload
     * @param array $copyData
     * @return array
     */
    protected function applyCopyToAds(array $payload, array $copyData): array
    {
        $ads = $payload['ads'] ?? [];

        if (empty($ads)) {
            Log::warning('[DRAFT_ENRICHMENT] No ads found in payload to apply copy');
            return $payload;
        }

        // Extract copy variants from enrichment payload
        $primaryTexts = $copyData['primary_texts'] ?? [];
        $headlines = $copyData['headlines'] ?? [];
        $descriptions = $copyData['descriptions'] ?? [];

        if (empty($primaryTexts)) {
            Log::warning('[DRAFT_ENRICHMENT] No primary_texts found in copy enrichment');
            return $payload;
        }

        Log::info('[DRAFT_ENRICHMENT] Applying copy to ads', [
            'ad_count' => count($ads),
            'primary_text_count' => count($primaryTexts),
            'headline_count' => count($headlines),
            'description_count' => count($descriptions),
        ]);

        // Apply copy to each ad
        foreach ($ads as $index => &$ad) {
            // Initialize structure if needed
            if (!isset($ad['creative']['object_story_spec']['link_data'])) {
                $ad['creative']['object_story_spec']['link_data'] = [];
            }

            $linkData = &$ad['creative']['object_story_spec']['link_data'];

            // Map copy by index, fallback to first variant
            $primaryTextIndex = $index < count($primaryTexts) ? $index : 0;
            $headlineIndex = $index < count($headlines) ? $index : 0;
            $descriptionIndex = $index < count($descriptions) ? $index : 0;

            // Apply primary text (message)
            if (!empty($primaryTexts[$primaryTextIndex])) {
                $linkData['message'] = $primaryTexts[$primaryTextIndex];
            }

            // Apply headline (name) if available
            if (!empty($headlines[$headlineIndex])) {
                $linkData['name'] = $headlines[$headlineIndex];
            }

            // Apply description if available
            if (!empty($descriptions[$descriptionIndex])) {
                $linkData['description'] = $descriptions[$descriptionIndex];
            }

            Log::info('[DRAFT_ENRICHMENT] Applied copy to ad', [
                'ad_index' => $index,
                'ad_name' => $ad['name'] ?? "Ad #{$index}",
                'primary_text_applied' => !empty($linkData['message']),
                'headline_applied' => !empty($linkData['name']),
                'description_applied' => !empty($linkData['description']),
            ]);
        }

        $payload['ads'] = $ads;

        Log::info('[DRAFT_ENRICHMENT] Copy application completed', [
            'ads_updated' => count($ads),
        ]);

        return $payload;
    }
}
