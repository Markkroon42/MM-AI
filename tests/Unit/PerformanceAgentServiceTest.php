<?php

namespace Tests\Unit;

use App\Models\MetaCampaign;
use App\Models\MetaInsightDaily;
use App\Services\Agents\CampaignAnalysisContextBuilder;
use App\Services\Agents\PerformanceAgentService;
use App\Services\Agents\RecommendationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceAgentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PerformanceAgentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PerformanceAgentService(
            new RecommendationFactory(),
            new CampaignAnalysisContextBuilder()
        );
    }

    public function test_detects_low_ctr()
    {
        $campaign = MetaCampaign::factory()->create();

        // Create insights with low CTR
        MetaInsightDaily::factory()->create([
            'entity_type' => 'campaign',
            'entity_local_id' => $campaign->id,
            'entity_meta_id' => $campaign->meta_campaign_id,
            'impressions' => 10000,
            'clicks' => 10, // 0.1% CTR, below threshold
            'ctr' => 0.1,
            'spend' => 100,
            'insight_date' => now()->subDays(1),
        ]);

        $recommendations = $this->service->analyzeCampaign($campaign, 7);

        $lowCtrRec = collect($recommendations)->firstWhere('recommendation_type', 'low_ctr');
        $this->assertNotNull($lowCtrRec);
    }

    public function test_detects_high_cpc()
    {
        $campaign = MetaCampaign::factory()->create();

        // Create insights with high CPC
        MetaInsightDaily::factory()->create([
            'entity_type' => 'campaign',
            'entity_local_id' => $campaign->id,
            'entity_meta_id' => $campaign->meta_campaign_id,
            'clicks' => 10,
            'spend' => 50, // $5 CPC, above threshold
            'cpc' => 5.0,
            'insight_date' => now()->subDays(1),
        ]);

        $recommendations = $this->service->analyzeCampaign($campaign, 7);

        $highCpcRec = collect($recommendations)->firstWhere('recommendation_type', 'high_cpc');
        $this->assertNotNull($highCpcRec);
    }

    public function test_detects_scale_winner()
    {
        $campaign = MetaCampaign::factory()->create();

        // Create insights with excellent performance
        MetaInsightDaily::factory()->create([
            'entity_type' => 'campaign',
            'entity_local_id' => $campaign->id,
            'entity_meta_id' => $campaign->meta_campaign_id,
            'spend' => 100,
            'purchases' => 15,
            'purchase_value' => 500, // 5.0x ROAS
            'roas' => 5.0,
            'insight_date' => now()->subDays(1),
        ]);

        $recommendations = $this->service->analyzeCampaign($campaign, 7);

        $scaleRec = collect($recommendations)->firstWhere('recommendation_type', 'scale_winner');
        $this->assertNotNull($scaleRec);
    }
}
