<?php

namespace App\Jobs\Meta;

use App\Services\Meta\MetaInsightSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMetaInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;

    protected int $campaignId;
    protected ?int $days;

    public function __construct(int $campaignId, ?int $days = null)
    {
        $this->campaignId = $campaignId;
        $this->days = $days;
    }

    public function handle(MetaInsightSyncService $syncService): void
    {
        Log::info('[META_SYNC_JOB] Starting insights sync job', [
            'campaign_id' => $this->campaignId,
            'days' => $this->days,
        ]);

        try {
            $syncRun = $syncService->syncDailyCampaignInsights($this->campaignId, $this->days);

            Log::info('[META_SYNC_JOB] Insights sync job completed', [
                'sync_run_id' => $syncRun->id,
                'records_processed' => $syncRun->records_processed,
            ]);
        } catch (\Exception $e) {
            Log::error('[META_SYNC_JOB] Insights sync job failed', [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
