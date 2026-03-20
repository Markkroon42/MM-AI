<?php

namespace App\Services\Meta;

use App\Enums\SyncStatusEnum;
use App\Models\MetaAdAccount;
use App\Models\SyncRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaAccountSyncService
{
    protected MetaApiClient $apiClient;

    public function __construct(MetaApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Sync all ad accounts from Meta
     */
    public function syncAllAccounts(?string $businessId = null): SyncRun
    {
        $syncRun = SyncRun::create([
            'provider' => 'meta',
            'sync_type' => 'ad_accounts',
            'status' => SyncStatusEnum::RUNNING->value,
            'started_at' => now(),
        ]);

        Log::info('[META_SYNC_ACCOUNTS] Starting account sync', [
            'sync_run_id' => $syncRun->id,
            'business_id' => $businessId,
        ]);

        try {
            $response = $this->apiClient->getAdAccounts($businessId);
            $accounts = $response['data'] ?? [];

            $recordsProcessed = 0;

            DB::beginTransaction();

            foreach ($accounts as $accountData) {
                $this->syncAccount($accountData);
                $recordsProcessed++;
            }

            DB::commit();

            $syncRun->update([
                'status' => SyncStatusEnum::SUCCESS->value,
                'finished_at' => now(),
                'records_processed' => $recordsProcessed,
            ]);

            Log::info('[META_SYNC_ACCOUNTS] Account sync completed successfully', [
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

            Log::error('[META_SYNC_ACCOUNTS] Account sync failed', [
                'sync_run_id' => $syncRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync a single account
     */
    protected function syncAccount(array $accountData): MetaAdAccount
    {
        $metaAccountId = $accountData['account_id'] ?? $accountData['id'];

        Log::info('[META_SYNC_ACCOUNTS] Syncing account', [
            'meta_account_id' => $metaAccountId,
            'name' => $accountData['name'] ?? null,
        ]);

        return MetaAdAccount::updateOrCreate(
            ['meta_account_id' => $metaAccountId],
            [
                'name' => $accountData['name'] ?? null,
                'business_name' => $accountData['business']['name'] ?? null,
                'currency' => $accountData['currency'] ?? null,
                'timezone_name' => $accountData['timezone_name'] ?? null,
                'status' => $accountData['account_status'] ?? null,
                'is_active' => true,
                'last_synced_at' => now(),
                'raw_payload_json' => $accountData,
            ]
        );
    }
}
