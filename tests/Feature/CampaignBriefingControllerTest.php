<?php

namespace Tests\Feature;

use App\Models\CampaignBriefing;
use App\Models\CampaignTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignBriefingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requires_auth(): void
    {
        $response = $this->get(route('admin.campaign-briefings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_create_stores_briefing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.campaign-briefings.store'), [
            'brand' => 'Test Brand',
            'market' => 'US',
            'objective' => 'Traffic',
            'product_name' => 'Product A',
            'target_audience' => 'Adults 25-45',
            'landing_page_url' => 'https://example.com',
            'budget_amount' => 1000.00,
            'campaign_goal' => 'Drive traffic to website',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaign_briefings', [
            'brand' => 'Test Brand',
            'market' => 'US',
            'created_by' => $user->id,
        ]);
    }

    public function test_generate_draft_creates_draft(): void
    {
        $user = User::factory()->create();
        $briefing = CampaignBriefing::factory()->create();
        $template = CampaignTemplate::factory()->create();

        $response = $this->actingAs($user)->post(
            route('admin.campaign-briefings.generate-draft', $briefing),
            ['template_id' => $template->id]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('campaign_drafts', [
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
        ]);
    }
}
