<?php

namespace App\Services\Meta;

use App\Exceptions\NonRetryablePublishException;
use App\Models\AuditLog;
use App\Models\CampaignDraft;
use App\Models\MetaCampaign;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class MetaCampaignWriteService
{
    public function __construct(
        protected MetaWriteClient $metaWriteClient
    ) {}

    /**
     * Pause a campaign
     */
    public function pauseCampaign(MetaCampaign $campaign): array
    {
        Log::info('[META_CAMPAIGN_WRITE] Pausing campaign', [
            'campaign_id' => $campaign->id,
            'meta_campaign_id' => $campaign->meta_campaign_id,
        ]);

        try {
            $response = $this->metaWriteClient->updateCampaignStatus(
                $campaign->meta_campaign_id,
                'PAUSED'
            );

            // Update local database
            $campaign->update([
                'status' => 'PAUSED',
            ]);

            AuditLog::log(
                'campaign_paused',
                $campaign,
                ['status' => $campaign->getOriginal('status')],
                ['status' => 'PAUSED'],
                ['meta_response' => $response]
            );

            Log::info('[META_CAMPAIGN_WRITE] Campaign paused successfully', [
                'campaign_id' => $campaign->id,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('[META_CAMPAIGN_WRITE] Failed to pause campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resume a campaign
     */
    public function resumeCampaign(MetaCampaign $campaign): array
    {
        Log::info('[META_CAMPAIGN_WRITE] Resuming campaign', [
            'campaign_id' => $campaign->id,
            'meta_campaign_id' => $campaign->meta_campaign_id,
        ]);

        try {
            $response = $this->metaWriteClient->updateCampaignStatus(
                $campaign->meta_campaign_id,
                'ACTIVE'
            );

            // Update local database
            $campaign->update([
                'status' => 'ACTIVE',
            ]);

            AuditLog::log(
                'campaign_resumed',
                $campaign,
                ['status' => $campaign->getOriginal('status')],
                ['status' => 'ACTIVE'],
                ['meta_response' => $response]
            );

            Log::info('[META_CAMPAIGN_WRITE] Campaign resumed successfully', [
                'campaign_id' => $campaign->id,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('[META_CAMPAIGN_WRITE] Failed to resume campaign', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update campaign budget
     */
    public function updateBudget(MetaCampaign $campaign, float $newBudget): array
    {
        Log::info('[META_CAMPAIGN_WRITE] Updating campaign budget', [
            'campaign_id' => $campaign->id,
            'meta_campaign_id' => $campaign->meta_campaign_id,
            'old_budget' => $campaign->daily_budget,
            'new_budget' => $newBudget,
        ]);

        try {
            $response = $this->metaWriteClient->updateCampaignBudget(
                $campaign->meta_campaign_id,
                $newBudget
            );

            // Update local database
            $oldBudget = $campaign->daily_budget;
            $campaign->update([
                'daily_budget' => $newBudget,
            ]);

            AuditLog::log(
                'campaign_budget_updated',
                $campaign,
                ['daily_budget' => $oldBudget],
                ['daily_budget' => $newBudget],
                ['meta_response' => $response]
            );

            Log::info('[META_CAMPAIGN_WRITE] Campaign budget updated successfully', [
                'campaign_id' => $campaign->id,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('[META_CAMPAIGN_WRITE] Failed to update campaign budget', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Publish a campaign draft
     * Fix: Proper account ID resolution and payload mapping
     */
    public function publishDraft(CampaignDraft $draft): array
    {
        Log::info('[META_CAMPAIGN_WRITE] Publishing campaign draft', [
            'draft_id' => $draft->id,
            'draft_name' => $draft->generated_name,
        ]);

        try {
            $payload = $draft->draft_payload_json;

            // Fix #1: Resolve account ID with proper fallback chain
            $accountId = $this->resolveAccountId($draft, $payload);

            // Fix #1 & #4: Validate account ID before proceeding (non-retryable error)
            if (empty($accountId)) {
                $errorMessage = 'No valid Meta ad account ID resolved for draft publish';
                Log::error('[META_CAMPAIGN_WRITE] ' . $errorMessage, [
                    'draft_id' => $draft->id,
                    'briefing_id' => $draft->briefing_id,
                    'template_id' => $draft->template_id,
                ]);
                throw new NonRetryablePublishException($errorMessage);
            }

            Log::info('[META_CAMPAIGN_WRITE] Resolved account ID for publish', [
                'account_id' => $accountId,
                'draft_id' => $draft->id,
            ]);

            // Fix #2 & #3: Translate draft payload to Meta API format using correct sources
            $metaPayload = $this->translateDraftToMetaFormat($draft, $payload);

            Log::info('[META_CAMPAIGN_WRITE] Prepared Meta campaign payload', [
                'draft_id' => $draft->id,
                'campaign_name' => $metaPayload['name'],
                'objective' => $metaPayload['objective'],
                'daily_budget' => $metaPayload['daily_budget'],
            ]);

            $response = $this->metaWriteClient->createCampaign($accountId, $metaPayload);

            AuditLog::log(
                'draft_published',
                $draft,
                null,
                ['meta_campaign_id' => $response['id'] ?? null],
                [
                    'meta_response' => $response,
                    'payload' => $metaPayload,
                    'account_id' => $accountId,
                ]
            );

            Log::info('[META_CAMPAIGN_WRITE] Campaign draft published successfully', [
                'draft_id' => $draft->id,
                'meta_campaign_id' => $response['id'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('[META_CAMPAIGN_WRITE] Failed to publish campaign draft', [
                'draft_id' => $draft->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resolve Meta ad account ID with fallback chain
     * Fix #1: Account ID source of truth
     */
    protected function resolveAccountId(CampaignDraft $draft, array $payload): ?string
    {
        // Try multiple sources in order of priority
        $sources = [
            'draft_payload' => $payload['meta_account_id'] ?? null,
            'briefing_meta_account' => $draft->briefing?->meta_account_id ?? null,
            'template_meta_account' => $draft->template?->meta_account_id ?? null,
            'system_setting' => SystemSetting::get('meta', 'default_account_id'),
            'config_default' => config('meta.default_account_id'),
        ];

        Log::info('[META_CAMPAIGN_WRITE] Starting account ID resolution', [
            'draft_id' => $draft->id,
            'sources_to_check' => array_keys($sources),
        ]);

        foreach ($sources as $source => $accountId) {
            // Validate that account ID is not empty and is a valid string
            if (!empty($accountId) && is_string($accountId) && trim($accountId) !== '') {
                Log::info('[META_CAMPAIGN_WRITE] Resolved account ID from source', [
                    'source' => $source,
                    'account_id' => $accountId,
                    'draft_id' => $draft->id,
                ]);
                return trim($accountId);
            }
        }

        Log::warning('[META_CAMPAIGN_WRITE] No valid account ID found in any source', [
            'draft_id' => $draft->id,
            'sources_checked' => array_keys($sources),
            'checked_values' => array_map(fn($v) => is_string($v) ? $v : gettype($v), $sources),
        ]);

        return null;
    }

    /**
     * Translate draft payload to Meta API format
     * Fix #2 & #3: Use correct payload sources (campaign.* structure)
     */
    protected function translateDraftToMetaFormat(CampaignDraft $draft, array $draftPayload): array
    {
        $campaign = $draftPayload['campaign'] ?? [];

        // Fix #3: Campaign name from correct source with proper fallback
        $name = $campaign['name'] ?? $draft->generated_name ?? 'Unnamed Campaign';

        // Fix #3: Objective from correct source with proper mapping
        $objective = $this->mapObjectiveToMeta($campaign['objective'] ?? $draft->briefing?->objective ?? 'OUTCOME_TRAFFIC');

        // Fix #3: Budget from correct source with fallbacks
        $dailyBudget = $campaign['daily_budget']
            ?? $draft->briefing?->budget_amount
            ?? $draft->template?->default_budget
            ?? null;

        // Convert to cents if present
        $dailyBudgetCents = $dailyBudget ? (int) ($dailyBudget * 100) : null;

        // Fix: Map internal status to valid Meta status
        $internalStatus = $campaign['status'] ?? $draft->status ?? 'draft';
        $metaStatus = $this->mapInternalStatusToMeta($internalStatus);

        $metaPayload = [
            'name' => $name,
            'objective' => $objective,
            'status' => $metaStatus,
            'special_ad_categories' => $campaign['special_ad_categories'] ?? [],
            'buying_type' => $campaign['buying_type'] ?? 'AUCTION',
            'daily_budget' => $dailyBudgetCents,
        ];

        Log::info('[META_CAMPAIGN_WRITE] Translated draft to Meta format', [
            'draft_id' => $draft->id,
            'source_campaign_name' => $campaign['name'] ?? null,
            'fallback_generated_name' => $draft->generated_name,
            'final_name' => $name,
            'source_objective' => $campaign['objective'] ?? null,
            'mapped_objective' => $objective,
            'internal_status' => $internalStatus,
            'mapped_meta_status' => $metaStatus,
            'daily_budget_euros' => $dailyBudget,
            'daily_budget_cents' => $dailyBudgetCents,
        ]);

        return $metaPayload;
    }

    /**
     * Map internal status to Meta campaign status
     * Fix: Convert internal draft statuses to valid Meta statuses
     *
     * Meta only accepts: ACTIVE, PAUSED, DELETED, ARCHIVED
     * Internal statuses like 'draft', 'ready_for_review', 'approved', etc. must be mapped
     */
    protected function mapInternalStatusToMeta(string $internalStatus): string
    {
        // Normalize to uppercase for comparison
        $normalized = strtoupper($internalStatus);

        // If already a valid Meta status, use it
        $validMetaStatuses = ['ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED'];
        if (in_array($normalized, $validMetaStatuses)) {
            Log::info('[META_CAMPAIGN_WRITE] Status already valid for Meta', [
                'internal_status' => $internalStatus,
                'meta_status' => $normalized,
            ]);
            return $normalized;
        }

        // Map internal statuses to Meta statuses
        // Default to PAUSED for safety - campaign is created but not active
        $mapping = [
            'draft' => 'PAUSED',
            'ready_for_review' => 'PAUSED',
            'approved' => 'PAUSED',
            'publishing' => 'PAUSED',
            'published' => 'PAUSED',
            'active' => 'ACTIVE',
            'paused' => 'PAUSED',
        ];

        $metaStatus = $mapping[strtolower($internalStatus)] ?? 'PAUSED';

        Log::info('[META_CAMPAIGN_WRITE] Mapped internal status to Meta status', [
            'internal_status' => $internalStatus,
            'meta_status' => $metaStatus,
        ]);

        return $metaStatus;
    }

    /**
     * Map internal objective to Meta objective format
     * Fix #3: Proper objective mapping
     */
    protected function mapObjectiveToMeta(string $objective): string
    {
        $mapping = [
            'leads' => 'OUTCOME_LEADS',
            'LEADS' => 'OUTCOME_LEADS',
            'traffic' => 'OUTCOME_TRAFFIC',
            'TRAFFIC' => 'OUTCOME_TRAFFIC',
            'awareness' => 'OUTCOME_AWARENESS',
            'AWARENESS' => 'OUTCOME_AWARENESS',
            'engagement' => 'OUTCOME_ENGAGEMENT',
            'ENGAGEMENT' => 'OUTCOME_ENGAGEMENT',
            'app_promotion' => 'OUTCOME_APP_PROMOTION',
            'APP_PROMOTION' => 'OUTCOME_APP_PROMOTION',
            'sales' => 'OUTCOME_SALES',
            'SALES' => 'OUTCOME_SALES',
        ];

        $mapped = $mapping[$objective] ?? null;

        if (!$mapped) {
            // If already in Meta format (starts with OUTCOME_), use as-is
            if (str_starts_with(strtoupper($objective), 'OUTCOME_')) {
                $mapped = strtoupper($objective);
            } else {
                // Default fallback
                $mapped = 'OUTCOME_TRAFFIC';
                Log::warning('[META_CAMPAIGN_WRITE] Unknown objective, using default', [
                    'original_objective' => $objective,
                    'mapped_objective' => $mapped,
                ]);
            }
        }

        return $mapped;
    }
}

