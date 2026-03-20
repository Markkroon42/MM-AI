<?php

namespace App\Console\Commands\Agents;

use App\Models\MetaAdAccount;
use App\Models\MetaCampaign;
use App\Services\Agents\RecommendationWriter;
use App\Services\Agents\StructureAgentService;
use Illuminate\Console\Command;

class RunStructureAgentCommand extends Command
{
    protected $signature = 'agents:run-structure
                            {--campaign= : Specific campaign ID to analyze}
                            {--account= : Analyze all campaigns for a specific account ID}';

    protected $description = 'Run structure analysis agent on campaigns';

    public function handle(
        StructureAgentService $structureAgent,
        RecommendationWriter $writer
    ): int {
        $this->info('Starting Structure Agent Analysis...');
        $this->newLine();

        // Determine which campaigns to analyze
        $campaigns = $this->getCampaignsToAnalyze();

        if ($campaigns->isEmpty()) {
            $this->error('No campaigns found to analyze.');
            return self::FAILURE;
        }

        $this->info("Analyzing {$campaigns->count()} campaign(s)...");
        $this->newLine();

        $totalRecommendations = 0;
        $results = [];

        foreach ($campaigns as $campaign) {
            $this->line("Analyzing campaign: {$campaign->name} (ID: {$campaign->id})");

            try {
                // Run structure analysis
                $recommendations = $structureAgent->analyzeCampaign($campaign);

                // Write recommendations to database
                $created = $writer->writeMany($recommendations);

                $totalRecommendations += count($created);

                $results[] = [
                    'campaign' => $campaign->name,
                    'recommendations' => count($created),
                    'status' => 'Success',
                ];

                $this->info("  Found " . count($created) . " recommendation(s)");

            } catch (\Exception $e) {
                $results[] = [
                    'campaign' => $campaign->name,
                    'recommendations' => 0,
                    'status' => 'Failed: ' . $e->getMessage(),
                ];

                $this->error("  Analysis failed: {$e->getMessage()}");
            }

            $this->newLine();
        }

        // Display summary table
        $this->newLine();
        $this->info('=== Analysis Summary ===');
        $this->table(
            ['Campaign', 'Recommendations', 'Status'],
            collect($results)->map(fn($r) => [
                $r['campaign'],
                $r['recommendations'],
                $r['status'],
            ])->toArray()
        );

        $this->newLine();
        $this->info("Total recommendations created: {$totalRecommendations}");

        return self::SUCCESS;
    }

    private function getCampaignsToAnalyze()
    {
        if ($campaignId = $this->option('campaign')) {
            $campaign = MetaCampaign::find($campaignId);

            if (!$campaign) {
                $this->error("Campaign with ID {$campaignId} not found.");
                return collect();
            }

            return collect([$campaign]);
        }

        if ($accountId = $this->option('account')) {
            $account = MetaAdAccount::find($accountId);

            if (!$account) {
                $this->error("Account with ID {$accountId} not found.");
                return collect();
            }

            return MetaCampaign::where('meta_ad_account_id', $accountId)->get();
        }

        // Analyze all campaigns
        return MetaCampaign::all();
    }
}
