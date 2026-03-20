<?php

namespace App\Jobs\Meta;

use App\Services\Meta\MetaCampaignSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMetaCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;

    protected int $accountId;

    public function __construct(int $accountId)
    {
        $this->accountId = $accountId;
    }

    public function handle(MetaCampaignSyncService $syncService): void
    {
        Log::info('[META_SYNC_JOB] Starting campaigns sync job', [
            'account_id' => $this->accountId,
        ]);

        try {
            $syncRun = $syncService->syncCampaignsForAccount($this->accountId);

            Log::info('[META_SYNC_JOB] Campaigns sync job completed', [
                'sync_run_id' => $syncRun->id,
                'records_processed' => $syncRun->records_processed,
            ]);
        } catch (\Exception $e) {
            Log::error('[META_SYNC_JOB] Campaigns sync job failed', [
                'account_id' => $this->accountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
