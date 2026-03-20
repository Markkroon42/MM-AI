<?php

namespace Tests\Feature;

use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;
use App\Models\CampaignTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignBuilderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function it_displays_the_campaign_briefing_builder()
    {
        $response = $this->get(route('admin.campaign-briefings.create'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.campaign-briefings.builder');
        $response->assertSee('Campaign Builder');
        $response->assertSee('Basic Information');
    }

    /** @test */
    public function it_displays_the_draft_builder_with_all_tabs()
    {
        $briefing = CampaignBriefing::factory()->create();
        $template = CampaignTemplate::factory()->create();

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'CONVERSIONS',
                    'daily_budget' => 100,
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test Ad Set',
                        'optimization_goal' => 'PURCHASE',
                    ],
                ],
                'ads' => [
                    [
                        'name' => 'Test Ad',
                        'creative' => [
                            'object_story_spec' => [
                                'link_data' => [
                                    'message' => 'Test primary text',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->get(route('admin.campaign-drafts.show', $draft));

        $response->assertStatus(200);
        $response->assertViewIs('admin.campaign-drafts.builder');
        $response->assertSee('Overview');
        $response->assertSee('Structure');
        $response->assertSee('Copy & Creatives');
        $response->assertSee('AI Enrichments');
        $response->assertSee('Review & Publish');
    }

    /** @test */
    public function it_shows_readiness_score_on_draft_page()
    {
        $briefing = CampaignBriefing::factory()->create();
        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
        ]);

        $response = $this->get(route('admin.campaign-drafts.show', $draft));

        $response->assertStatus(200);
        $response->assertSee('Campaign Readiness');
        $response->assertSee('%');
    }

    /** @test */
    public function it_shows_validation_warnings_for_incomplete_draft()
    {
        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => [
                'campaign' => [],
                'ad_sets' => [],
                'ads' => [],
            ],
        ]);

        $response = $this->get(route('admin.campaign-drafts.show', $draft));

        $response->assertStatus(200);
        $response->assertSee('Publish Blockers');
    }

    /** @test */
    public function it_shows_campaign_structure_in_visual_tree()
    {
        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Visual Test Campaign',
                    'objective' => 'CONVERSIONS',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Ad Set 1',
                        'optimization_goal' => 'PURCHASE',
                    ],
                ],
                'ads' => [
                    [
                        'name' => 'Ad 1',
                        'creative' => [],
                    ],
                ],
            ],
        ]);

        $response = $this->get(route('admin.campaign-drafts.show', $draft));

        $response->assertStatus(200);
        $response->assertSee('Visual Test Campaign');
        $response->assertSee('Ad Set 1');
        $response->assertSee('Ad 1');
    }
}
