<?php

namespace App\Services\Meta;

use App\Enums\MetaEntityTypeEnum;
use App\Enums\SyncStatusEnum;
use App\Models\MetaCampaign;
use App\Models\MetaInsightDaily;
use App\Models\SyncRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaInsightSyncService
{
    protected MetaApiClient $apiClient;

    public function __construct(MetaApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Sync daily campaign insights
     */
    public function syncDailyCampaignInsights(int $campaignId, ?int $days = null): SyncRun
    {
        $campaign = MetaCampaign::with('metaAdAccount')->findOrFail($campaignId);
        $days = $days ?? config('meta.sync.default_insights_days', 30);

        $syncRun = SyncRun::create([
            'provider' => 'meta',
            'account_id' => $campaign->meta_ad_account_id,
            'sync_type' => 'campaign_insights',
            'status' => SyncStatusEnum::RUNNING->value,
            'started_at' => now(),
            'meta_json' => [
                'campaign_id' => $campaignId,
                'meta_campaign_id' => $campaign->meta_campaign_id,
                'days' => $days,
            ],
        ]);

        Log::info('[META_SYNC_INSIGHTS] Starting campaign insights sync', [
            'sync_run_id' => $syncRun->id,
            'campaign_id' => $campaignId,
            'meta_campaign_id' => $campaign->meta_campaign_id,
            'days' => $days,
        ]);

        try {
            $dateFrom = Carbon::now()->subDays($days)->format('Y-m-d');
            $dateTo = Carbon::now()->format('Y-m-d');

            $response = $this->apiClient->getCampaignInsights(
                $campaign->meta_campaign_id,
                $dateFrom,
                $dateTo
            );

            $insights = $response['data'] ?? [];
            $recordsProcessed = 0;

            DB::beginTransaction();

            foreach ($insights as $insightData) {
                $this->syncInsight(
                    MetaEntityTypeEnum::CAMPAIGN,
                    $campaign->id,
                    $campaign->meta_campaign_id,
                    $insightData
                );
                $recordsProcessed++;
            }

            DB::commit();

            $syncRun->update([
                'status' => SyncStatusEnum::SUCCESS->value,
                'finished_at' => now(),
                'records_processed' => $recordsProcessed,
            ]);

            Log::info('[META_SYNC_INSIGHTS] Campaign insights sync completed successfully', [
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

            Log::error('[META_SYNC_INSIGHTS] Campaign insights sync failed', [
                'sync_run_id' => $syncRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync a single insight record
     */
    protected function syncInsight(
        MetaEntityTypeEnum $entityType,
        int $entityLocalId,
        string $entityMetaId,
        array $insightData
    ): MetaInsightDaily {
        $insightDate = $insightData['date_start'] ?? now()->format('Y-m-d');

        Log::info('[META_SYNC_INSIGHTS] Syncing insight', [
            'entity_type' => $entityType->value,
            'entity_local_id' => $entityLocalId,
            'entity_meta_id' => $entityMetaId,
            'insight_date' => $insightDate,
        ]);

        // Parse actions
        $actions = $this->parseActions($insightData['actions'] ?? []);
        $actionValues = $this->parseActions($insightData['action_values'] ?? []);

        return MetaInsightDaily::updateOrCreate(
            [
                'entity_type' => $entityType->value,
                'entity_local_id' => $entityLocalId,
                'insight_date' => $insightDate,
            ],
            [
                'entity_meta_id' => $entityMetaId,
                'impressions' => $insightData['impressions'] ?? 0,
                'reach' => $insightData['reach'] ?? 0,
                'clicks' => $insightData['clicks'] ?? 0,
                'link_clicks' => $insightData['inline_link_clicks'] ?? 0,
                'ctr' => $insightData['ctr'] ?? 0,
                'cpc' => $insightData['cpc'] ?? 0,
                'cpm' => $insightData['cpm'] ?? 0,
                'spend' => $insightData['spend'] ?? 0,
                'add_to_cart' => $actions['add_to_cart'] ?? 0,
                'initiate_checkout' => $actions['initiate_checkout'] ?? 0,
                'purchases' => $actions['purchase'] ?? 0,
                'purchase_value' => $actionValues['purchase'] ?? 0,
                'roas' => $this->calculateRoas($actionValues['purchase'] ?? 0, $insightData['spend'] ?? 0),
                'frequency' => $insightData['frequency'] ?? 0,
                'raw_payload_json' => $insightData,
            ]
        );
    }

    /**
     * Parse actions array from Meta API response
     */
    protected function parseActions(array $actions): array
    {
        $parsed = [];

        foreach ($actions as $action) {
            $actionType = $action['action_type'] ?? null;
            $value = $action['value'] ?? 0;

            if ($actionType) {
                $parsed[$actionType] = $value;
            }
        }

        return $parsed;
    }

    /**
     * Calculate ROAS (Return on Ad Spend)
     */
    protected function calculateRoas(float $revenue, float $spend): float
    {
        if ($spend <= 0) {
            return 0;
        }

        return round($revenue / $spend, 4);
    }
}
