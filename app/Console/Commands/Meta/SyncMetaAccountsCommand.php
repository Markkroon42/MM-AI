<?php

namespace App\Console\Commands\Meta;

use App\Services\Meta\MetaAccountSyncService;
use Illuminate\Console\Command;

class SyncMetaAccountsCommand extends Command
{
    protected $signature = 'meta:sync-accounts {--business-id= : Business ID to sync accounts from}'; //481575405852950

    protected $description = 'Sync Meta ad accounts from the Meta Graph API';

    public function handle(MetaAccountSyncService $syncService): int
    {
        $this->info('Starting Meta ad accounts sync...');

        try {
            $businessId = $this->option('business-id');

            $syncRun = $syncService->syncAllAccounts($businessId);

            $this->info("Sync completed successfully!");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Sync Run ID', $syncRun->id],
                    ['Status', $syncRun->status],
                    ['Records Processed', $syncRun->records_processed],
                    ['Duration (seconds)', $syncRun->duration ?? 'N/A'],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
