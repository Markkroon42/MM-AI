<?php

namespace App\Console\Commands\Meta;

use App\Models\MetaAdAccount;
use App\Services\Meta\MetaCampaignSyncService;
use App\Services\Meta\MetaInsightSyncService;
use Illuminate\Console\Command;

class SyncMetaCampaignsCommand extends Command
{
    protected $signature = 'meta:sync-campaigns 
                            {--account= : Specific account ID to sync}
                            {--with-insights : Also sync insights for campaigns}
                            {--days=30 : Number of days of insights to sync}';

    protected $description = 'Sync Meta campaigns, ad sets, and ads from the Meta Graph API';

    public function handle(
        MetaCampaignSyncService $campaignSyncService,
        MetaInsightSyncService $insightSyncService
    ): int {
        $this->info('Starting Meta campaigns sync...');

        try {
            $accountId = $this->option('account');
            $withInsights = $this->option('with-insights');
            $days = (int) $this->option('days');

            if ($accountId) {
                $accounts = [MetaAdAccount::findOrFail($accountId)];
            } else {
                $accounts = MetaAdAccount::where('is_active', true)->get();
            }

            if ($accounts->isEmpty()) {
                $this->warn('No active accounts found to sync.');
                return Command::SUCCESS;
            }

            $this->info("Found {$accounts->count()} account(s) to sync");

            foreach ($accounts as $account) {
                $this->info("Syncing account: {$account->name} ({$account->id})");

                $syncRun = $campaignSyncService->syncCampaignsForAccount($account->id);

                $this->info("  - Campaigns synced: {$syncRun->records_processed}");

                if ($withInsights) {
                    $this->info("  - Syncing insights for campaigns...");
                    
                    foreach ($account->campaigns as $campaign) {
                        $insightSyncRun = $insightSyncService->syncDailyCampaignInsights(
                            $campaign->id,
                            $days
                        );
                        
                        $this->info("    - Campaign '{$campaign->name}': {$insightSyncRun->records_processed} insight records");
                    }
                }
            }

            $this->info('All syncs completed successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sync failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
