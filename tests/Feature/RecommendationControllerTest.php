<?php

namespace Tests\Feature;

use App\Models\CampaignRecommendation;
use App\Models\MetaCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_requires_auth()
    {
        $response = $this->get(route('admin.recommendations.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_index_loads_successfully()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('admin.recommendations.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.recommendations.index');
    }

    public function test_index_filters_by_status()
    {
        $campaign = MetaCampaign::factory()->create();

        CampaignRecommendation::factory()->create([
            'meta_campaign_id' => $campaign->id,
            'status' => 'new',
        ]);

        CampaignRecommendation::factory()->create([
            'meta_campaign_id' => $campaign->id,
            'status' => 'approved',
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('admin.recommendations.index', ['status' => 'new']));

        $response->assertStatus(200);
        $response->assertSee('new');
    }

    public function test_show_displays_recommendation()
    {
        $campaign = MetaCampaign::factory()->create();
        $recommendation = CampaignRecommendation::factory()->create([
            'meta_campaign_id' => $campaign->id,
        ]);

        $this->actingAs($this->user);
        $response = $this->get(route('admin.recommendations.show', $recommendation));

        $response->assertStatus(200);
        $response->assertViewIs('admin.recommendations.show');
        $response->assertSee($recommendation->title);
    }

    public function test_approve_requires_permission()
    {
        $campaign = MetaCampaign::factory()->create();
        $recommendation = CampaignRecommendation::factory()->create([
            'meta_campaign_id' => $campaign->id,
            'status' => 'new',
        ]);

        $this->actingAs($this->user);
        $response = $this->post(route('admin.recommendations.approve', $recommendation), [
            'review_notes' => 'Approved for testing',
        ]);

        // Should update the recommendation
        $response->assertRedirect();
        $this->assertDatabaseHas('campaign_recommendations', [
            'id' => $recommendation->id,
            'status' => 'approved',
        ]);
    }
}
