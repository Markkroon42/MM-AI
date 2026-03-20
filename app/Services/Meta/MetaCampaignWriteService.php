<?php

namespace App\Services\Meta;

use App\Models\AuditLog;
use App\Models\CampaignDraft;
use App\Models\MetaCampaign;
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
     */
    public function publishDraft(CampaignDraft $draft): array
    {
        Log::info('[META_CAMPAIGN_WRITE] Publishing campaign draft', [
            'draft_id' => $draft->id,
            'draft_name' => $draft->generated_name,
        ]);

        try {
            $payload = $draft->draft_payload_json;

            // Translate draft payload to Meta API format
            $metaPayload = $this->translateDraftToMetaFormat($payload);

            // Get account ID from payload or configuration
            $accountId = $payload['account_id'] ?? config('meta.default_account_id');

            $response = $this->metaWriteClient->createCampaign($accountId, $metaPayload);

            AuditLog::log(
                'draft_published',
                $draft,
                null,
                ['meta_campaign_id' => $response['id'] ?? null],
                [
                    'meta_response' => $response,
                    'payload' => $metaPayload,
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
     * Translate draft payload to Meta API format
     */
    protected function translateDraftToMetaFormat(array $draftPayload): array
    {
        return [
            'name' => $draftPayload['name'] ?? 'Unnamed Campaign',
            'objective' => $draftPayload['objective'] ?? 'OUTCOME_TRAFFIC',
            'status' => $draftPayload['status'] ?? 'PAUSED',
            'special_ad_categories' => $draftPayload['special_ad_categories'] ?? [],
            'buying_type' => $draftPayload['buying_type'] ?? 'AUCTION',
            'daily_budget' => isset($draftPayload['daily_budget']) ? (int) ($draftPayload['daily_budget'] * 100) : null,
        ];
    }
}
