<?php

namespace App\Jobs\Meta;

use App\Services\Meta\MetaAccountSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMetaAccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected ?string $businessId;

    public function __construct(?string $businessId = null)
    {
        $this->businessId = $businessId;
    }

    public function handle(MetaAccountSyncService $syncService): void
    {
        Log::info('[META_SYNC_JOB] Starting accounts sync job', [
            'business_id' => $this->businessId,
        ]);

        try {
            $syncRun = $syncService->syncAllAccounts($this->businessId);

            Log::info('[META_SYNC_JOB] Accounts sync job completed', [
                'sync_run_id' => $syncRun->id,
                'records_processed' => $syncRun->records_processed,
            ]);
        } catch (\Exception $e) {
            Log::error('[META_SYNC_JOB] Accounts sync job failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
