<?php

namespace Tests\Unit;

use App\Models\CampaignRecommendation;
use App\Models\MetaCampaign;
use App\Services\Agents\RecommendationWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationWriterTest extends TestCase
{
    use RefreshDatabase;

    protected RecommendationWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->writer = new RecommendationWriter();
    }

    public function test_prevents_duplicate_recommendations()
    {
        $campaign = MetaCampaign::factory()->create();

        $data = [
            'meta_campaign_id' => $campaign->id,
            'recommendation_type' => 'low_ctr',
            'severity' => 'medium',
            'title' => 'Test Recommendation',
            'explanation' => 'Test explanation',
            'proposed_action' => 'Test action',
            'source_agent' => 'performance_agent',
            'confidence_score' => 75.0,
            'status' => 'new',
        ];

        // First write should succeed
        $recommendation1 = $this->writer->write($data);
        $this->assertNotNull($recommendation1);

        // Second write with same data should return null (duplicate)
        $recommendation2 = $this->writer->write($data);
        $this->assertNull($recommendation2);

        // Verify only one recommendation exists
        $count = CampaignRecommendation::where('meta_campaign_id', $campaign->id)
            ->where('recommendation_type', 'low_ctr')
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_saves_recommendation_successfully()
    {
        $campaign = MetaCampaign::factory()->create();

        $data = [
            'meta_campaign_id' => $campaign->id,
            'recommendation_type' => 'high_cpc',
            'severity' => 'high',
            'title' => 'High CPC Detected',
            'explanation' => 'Your CPC is too high',
            'proposed_action' => 'Lower bids',
            'source_agent' => 'performance_agent',
            'confidence_score' => 80.0,
            'status' => 'new',
        ];

        $recommendation = $this->writer->write($data);

        $this->assertNotNull($recommendation);
        $this->assertDatabaseHas('campaign_recommendations', [
            'id' => $recommendation->id,
            'recommendation_type' => 'high_cpc',
            'title' => 'High CPC Detected',
        ]);
    }
}
