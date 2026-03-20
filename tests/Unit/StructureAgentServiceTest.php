<?php

namespace Tests\Unit;

use App\Models\MetaCampaign;
use App\Services\Agents\RecommendationFactory;
use App\Services\Agents\StructureAgentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructureAgentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StructureAgentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StructureAgentService(new RecommendationFactory());
    }

    public function test_detects_no_ad_sets()
    {
        $campaign = MetaCampaign::factory()->create();

        $recommendations = $this->service->analyzeCampaign($campaign);

        $this->assertNotEmpty($recommendations);
        $this->assertEquals('no_ad_sets', $recommendations[0]['recommendation_type']);
    }

    public function test_detects_naming_violation()
    {
        $campaign = MetaCampaign::factory()->create([
            'name' => 'TestCampaign', // Only 1 segment, needs at least 3
        ]);

        $recommendations = $this->service->analyzeCampaign($campaign);

        $namingViolation = collect($recommendations)->firstWhere('recommendation_type', 'naming_violation');
        $this->assertNotNull($namingViolation);
    }

    public function test_creates_agent_run_record()
    {
        $campaign = MetaCampaign::factory()->create();

        $recommendations = $this->service->analyzeCampaign($campaign);

        $this->assertDatabaseHas('agent_runs', [
            'agent_name' => 'structure_agent',
            'scope_type' => 'campaign',
            'scope_id' => $campaign->id,
            'status' => 'success',
        ]);
    }
}
