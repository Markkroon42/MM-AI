<?php

namespace App\Services\Meta;

use App\Enums\SyncStatusEnum;
use App\Models\MetaAdAccount;
use App\Models\MetaCampaign;
use App\Models\MetaAdSet;
use App\Models\MetaAd;
use App\Models\SyncRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaCampaignSyncService
{
    protected MetaApiClient $apiClient;

    public function __construct(MetaApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Sync campaigns for a specific ad account
     */
    public function syncCampaignsForAccount(int $accountId): SyncRun
    {
        $account = MetaAdAccount::findOrFail($accountId);

        $syncRun = SyncRun::create([
            'provider' => 'meta',
            'account_id' => $accountId,
            'sync_type' => 'campaigns',
            'status' => SyncStatusEnum::RUNNING->value,
            'started_at' => now(),
        ]);

        Log::info('[META_SYNC_CAMPAIGNS] Starting campaign sync', [
            'sync_run_id' => $syncRun->id,
            'account_id' => $accountId,
            'meta_account_id' => $account->meta_account_id,
        ]);

        try {
            $adAccountId = "act_{$account->meta_account_id}";
            $response = $this->apiClient->getCampaigns($adAccountId);
            $campaigns = $response['data'] ?? [];

            $recordsProcessed = 0;

            DB::beginTransaction();

            foreach ($campaigns as $campaignData) {
                $this->syncCampaign($account, $campaignData);
                $recordsProcessed++;
            }

            DB::commit();

            // Update account sync timestamp
            $account->update(['last_synced_at' => now()]);

            $syncRun->update([
                'status' => SyncStatusEnum::SUCCESS->value,
                'finished_at' => now(),
                'records_processed' => $recordsProcessed,
            ]);

            Log::info('[META_SYNC_CAMPAIGNS] Campaign sync completed successfully', [
                'sync_run_id' => $syncRun->id,
                'records_processed' => $recordsProcessed,
            ]);

            return $syncRun;
        } catch (\Exception $e) {
            DB::rollBack();

            $syncRun->update([
                'status' => SyncStatusEnum::FAILED->value,
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error('[META_SYNC_CAMPAIGNS] Campaign sync failed', [
                'sync_run_id' => $syncRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync a single campaign
     */
    protected function syncCampaign(MetaAdAccount $account, array $campaignData): MetaCampaign
    {
        $metaCampaignId = $campaignData['id'];

        Log::info('[META_SYNC_CAMPAIGNS] Syncing campaign', [
            'meta_campaign_id' => $metaCampaignId,
            'name' => $campaignData['name'] ?? null,
        ]);

        return MetaCampaign::updateOrCreate(
            ['meta_campaign_id' => $metaCampaignId],
            [
                'meta_ad_account_id' => $account->id,
                'name' => $campaignData['name'] ?? null,
                'objective' => $campaignData['objective'] ?? null,
                'buying_type' => $campaignData['buying_type'] ?? null,
                'status' => $campaignData['status'] ?? null,
                'effective_status' => $campaignData['effective_status'] ?? null,
                'daily_budget' => isset($campaignData['daily_budget']) ? $campaignData['daily_budget'] / 100 : null,
                'lifetime_budget' => isset($campaignData['lifetime_budget']) ? $campaignData['lifetime_budget'] / 100 : null,
                'start_time' => $campaignData['start_time'] ?? null,
                'stop_time' => $campaignData['stop_time'] ?? null,
                'source_updated_at' => $campaignData['updated_time'] ?? null,
                'last_synced_at' => now(),
                'raw_payload_json' => $campaignData,
            ]
        );
    }

    /**
     * Sync ad sets for a campaign
     */
    public function syncAdSetsForCampaign(int $campaignId): int
    {
        $campaign = MetaCampaign::findOrFail($campaignId);

        Log::info('[META_SYNC_CAMPAIGNS] Starting ad set sync', [
            'campaign_id' => $campaignId,
            'meta_campaign_id' => $campaign->meta_campaign_id,
        ]);

        try {
            $response = $this->apiClient->getAdSets($campaign->meta_campaign_id);
            $adSets = $response['data'] ?? [];

            $recordsProcessed = 0;

            DB::beginTransaction();

            foreach ($adSets as $adSetData) {
                $this->syncAdSet($campaign, $adSetData);
                $recordsProcessed++;
            }

            DB::commit();

            Log::info('[META_SYNC_CAMPAIGNS] Ad set sync completed', [
                'campaign_id' => $campaignId,
                'records_processed' => $recordsProcessed,
            ]);

            return $recordsProcessed;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[META_SYNC_CAMPAIGNS] Ad set sync failed', [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync a single ad set
     */
    protected function syncAdSet(MetaCampaign $campaign, array $adSetData): MetaAdSet
    {
        $metaAdSetId = $adSetData['id'];

        Log::info('[META_SYNC_CAMPAIGNS] Syncing ad set', [
            'meta_ad_set_id' => $metaAdSetId,
            'name' => $adSetData['name'] ?? null,
        ]);

        return MetaAdSet::updateOrCreate(
            ['meta_ad_set_id' => $metaAdSetId],
            [
                'meta_campaign_id' => $campaign->id,
                'name' => $adSetData['name'] ?? null,
                'optimization_goal' => $adSetData['optimization_goal'] ?? null,
                'billing_event' => $adSetData['billing_event'] ?? null,
                'bid_strategy' => $adSetData['bid_strategy'] ?? null,
                'targeting_json' => $adSetData['targeting'] ?? null,
                'daily_budget' => isset($adSetData['daily_budget']) ? $adSetData['daily_budget'] / 100 : null,
                'lifetime_budget' => isset($adSetData['lifetime_budget']) ? $adSetData['lifetime_budget'] / 100 : null,
                'status' => $adSetData['status'] ?? null,
                'effective_status' => $adSetData['effective_status'] ?? null,
                'start_time' => $adSetData['start_time'] ?? null,
                'end_time' => $adSetData['end_time'] ?? null,
                'source_updated_at' => $adSetData['updated_time'] ?? null,
                'last_synced_at' => now(),
                'raw_payload_json' => $adSetData,
            ]
        );
    }

    /**
     * Sync ads for an ad set
     */
    public function syncAdsForAdSet(int $adSetId): int
    {
        $adSet = MetaAdSet::findOrFail($adSetId);

        Log::info('[META_SYNC_CAMPAIGNS] Starting ad sync', [
            'ad_set_id' => $adSetId,
            'meta_ad_set_id' => $adSet->meta_ad_set_id,
        ]);

        try {
            $response = $this->apiClient->getAds($adSet->meta_ad_set_id);
            $ads = $response['data'] ?? [];

            $recordsProcessed = 0;

            DB::beginTransaction();

            foreach ($ads as $adData) {
                $this->syncAd($adSet, $adData);
                $recordsProcessed++;
            }

            DB::commit();

            Log::info('[META_SYNC_CAMPAIGNS] Ad sync completed', [
                'ad_set_id' => $adSetId,
                'records_processed' => $recordsProcessed,
            ]);

            return $recordsProcessed;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[META_SYNC_CAMPAIGNS] Ad sync failed', [
                'ad_set_id' => $adSetId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync a single ad
     */
    protected function syncAd(MetaAdSet $adSet, array $adData): MetaAd
    {
        $metaAdId = $adData['id'];

        Log::info('[META_SYNC_CAMPAIGNS] Syncing ad', [
            'meta_ad_id' => $metaAdId,
            'name' => $adData['name'] ?? null,
        ]);

        return MetaAd::updateOrCreate(
            ['meta_ad_id' => $metaAdId],
            [
                'meta_ad_set_id' => $adSet->id,
                'name' => $adData['name'] ?? null,
                'status' => $adData['status'] ?? null,
                'effective_status' => $adData['effective_status'] ?? null,
                'creative_meta_id' => $adData['creative']['id'] ?? null,
                'preview_url' => $adData['preview_shareable_link'] ?? null,
                'source_updated_at' => $adData['updated_time'] ?? null,
                'last_synced_at' => now(),
                'raw_payload_json' => $adData,
            ]
        );
    }
}
