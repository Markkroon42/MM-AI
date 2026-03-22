<?php

namespace Tests\Feature;

use App\Enums\DraftEnrichmentStatusEnum;
use App\Enums\DraftEnrichmentTypeEnum;
use App\Models\CampaignDraft;
use App\Models\DraftEnrichment;
use App\Models\User;
use App\Services\AI\DraftEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreativeSuggestionEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CampaignDraft $draft;
    protected DraftEnrichmentService $enrichmentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();

        $this->user = User::factory()->create();

        $briefing = \App\Models\CampaignBriefing::factory()->create();
        $template = \App\Models\CampaignTemplate::factory()->create();

        $this->draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'draft_payload_json' => [],
        ]);
        $this->enrichmentService = app(DraftEnrichmentService::class);
    }

    /** @test */
    public function it_displays_creative_suggestion_content_with_structured_payload()
    {
        // Given: Creative suggestion enrichment with structured payload
        $enrichment = DraftEnrichment::create([
            'campaign_draft_id' => $this->draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value,
            'status' => DraftEnrichmentStatusEnum::DRAFT->value,
            'payload_json' => [
                'suggestions' => [
                    [
                        'title' => 'Test Creative Concept',
                        'hook' => 'Attention-grabbing opening line',
                        'format' => 'Video',
                        'description' => 'A compelling description',
                        'angle' => 'Problem-solution approach',
                        'cta_direction' => 'Learn more',
                        'target_audience_context' => 'Young professionals',
                        'body' => 'Full creative body text',
                        'summary' => 'Brief summary',
                    ],
                ],
            ],
            'created_by' => $this->user->id,
        ]);

        // When: Viewing the draft detail page
        $response = $this->actingAs($this->user)
            ->get(route('admin.campaign-drafts.show', $this->draft));

        // Then: All creative suggestion fields should be visible
        $response->assertStatus(200);

        // Debug: Check if enrichments are present in the HTML
        if (!str_contains($response->getContent(), 'Test Creative Concept')) {
            file_put_contents('/tmp/test_response.html', $response->getContent());
            $this->fail('Content not found in response. HTML saved to /tmp/test_response.html');
        }

        $response->assertSee('Test Creative Concept');
        $response->assertSee('Attention-grabbing opening line');
        $response->assertSee('Video');
        $response->assertSee('A compelling description');
        $response->assertSee('Problem-solution approach');
        $response->assertSee('Learn more');
        $response->assertSee('Young professionals');
        $response->assertSee('Full creative body text');
        $response->assertSee('Brief summary');
    }

    /** @test */
    public function it_displays_fallback_for_legacy_creative_format()
    {
        // Given: Creative suggestion with legacy format (visual ideas)
        $enrichment = DraftEnrichment::create([
            'campaign_draft_id' => $this->draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value,
            'status' => DraftEnrichmentStatusEnum::DRAFT->value,
            'payload_json' => [
                'static_visual_ideas' => [
                    'Visual idea 1',
                    'Visual idea 2',
                ],
                'video_concepts' => [
                    'Video concept 1',
                ],
            ],
            'created_by' => $this->user->id,
        ]);

        // When: Viewing the draft detail page
        $response = $this->actingAs($this->user)
            ->get(route('admin.campaign-drafts.show', $this->draft));

        // Then: Legacy format should be displayed
        $response->assertStatus(200);
        $response->assertSee('Static Visual Ideas');
        $response->assertSee('Visual idea 1');
        $response->assertSee('Video Concepts');
        $response->assertSee('Video concept 1');
    }

    /** @test */
    public function it_displays_warning_for_empty_payload()
    {
        // Given: Creative suggestion with empty payload
        $enrichment = DraftEnrichment::create([
            'campaign_draft_id' => $this->draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value,
            'status' => DraftEnrichmentStatusEnum::DRAFT->value,
            'payload_json' => [],
            'created_by' => $this->user->id,
        ]);

        // When: Viewing the draft detail page
        $response = $this->actingAs($this->user)
            ->get(route('admin.campaign-drafts.show', $this->draft));

        // Then: Warning message should be displayed
        $response->assertStatus(200);
        $response->assertSee('No creative suggestion content available');
    }

    /** @test */
    public function it_displays_use_this_button_for_draft_status()
    {
        // Given: Creative suggestion in draft status
        $enrichment = DraftEnrichment::create([
            'campaign_draft_id' => $this->draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value,
            'status' => DraftEnrichmentStatusEnum::DRAFT->value,
            'payload_json' => [
                'suggestions' => [
                    ['title' => 'Test Suggestion'],
                ],
            ],
            'created_by' => $this->user->id,
        ]);

        // When: Viewing the draft detail page
        $response = $this->actingAs($this->user)
            ->get(route('admin.campaign-drafts.show', $this->draft));

        // Then: Use This button should be visible
        $response->assertStatus(200);
        $response->assertSee('Use This');
        $response->assertSee(route('admin.draft-enrichments.apply', $enrichment));
    }

    /** @test */
    public function it_applies_creative_suggestion_via_use_this_button()
    {
        // Given: Creative suggestion in draft status
        $enrichment = DraftEnrichment::create([
            'campaign_draft_id' => $this->draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value,
            'status' => DraftEnrichmentStatusEnum::DRAFT->value,
            'payload_json' => [
                'suggestions' => [
                    [
                        'title' => 'Test Creative',
                        'hook' => 'Great hook',
                    ],
                ],
            ],
            'created_by' => $this->user->id,
        ]);

        // When: Clicking Use This button
        $response = $this->actingAs($this->user)
            ->post(route('admin.draft-enrichments.apply', $enrichment));

        // Then: Enrichment should be applied
        $response->assertRedirect(route('admin.campaign-drafts.show', $this->draft));
        $response->assertSessionHas('success', 'Enrichment applied to draft successfully!');

        // And: Status should be updated to applied
        $this->assertEquals(
            DraftEnrichmentStatusEnum::APPLIED->value,
            $enrichment->fresh()->status
        );

        // And: Enrichment should be stored in draft payload
        $draftPayload = $this->draft->fresh()->draft_payload_json;
        $this->assertArrayHasKey('ai_enrichments', $draftPayload);
        $this->assertArrayHasKey('creative_suggestions', $draftPayload['ai_enrichments']);
        $this->assertEquals(
            $enrichment->id,
            $draftPayload['ai_enrichments']['creative_suggestions']['enrichment_id']
        );
    }

    /** @test */
    public function it_stores_creative_suggestion_metadata_on_apply()
    {
        // Given: Creative suggestion enrichment
        $enrichment = DraftEnrichment::create([
            'campaign_draft_id' => $this->draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value,
            'status' => DraftEnrichmentStatusEnum::DRAFT->value,
            'payload_json' => [
                'suggestions' => [
                    ['title' => 'Test'],
                ],
            ],
            'created_by' => $this->user->id,
        ]);

        // When: Applying the enrichment
        $this->enrichmentService->applyEnrichment($enrichment, $this->user);

        // Then: Metadata should be stored correctly
        $draftPayload = $this->draft->fresh()->draft_payload_json;
        $appliedData = $draftPayload['ai_enrichments']['creative_suggestions'];

        $this->assertEquals($enrichment->id, $appliedData['enrichment_id']);
        $this->assertEquals($this->user->id, $appliedData['applied_by']);
        $this->assertArrayHasKey('applied_at', $appliedData);
        $this->assertArrayHasKey('data', $appliedData);
        $this->assertEquals(
            $enrichment->payload_json,
            $appliedData['data']
        );
    }

    /** @test */
    public function it_shows_applied_status_after_using_creative_suggestion()
    {
        // Given: Applied creative suggestion
        $enrichment = DraftEnrichment::create([
            'campaign_draft_id' => $this->draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value,
            'status' => DraftEnrichmentStatusEnum::APPLIED->value,
            'payload_json' => [
                'suggestions' => [
                    ['title' => 'Test'],
                ],
            ],
            'created_by' => $this->user->id,
        ]);

        // When: Viewing the draft detail page
        $response = $this->actingAs($this->user)
            ->get(route('admin.campaign-drafts.show', $this->draft));

        // Then: Applied status should be shown
        $response->assertStatus(200);
        $response->assertSee('This enrichment has been applied to the draft');
    }

    /** @test */
    public function it_handles_multiple_creative_suggestions_in_payload()
    {
        // Given: Multiple creative suggestions
        $enrichment = DraftEnrichment::create([
            'campaign_draft_id' => $this->draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::CREATIVE_SUGGESTIONS->value,
            'status' => DraftEnrichmentStatusEnum::DRAFT->value,
            'payload_json' => [
                'suggestions' => [
                    ['title' => 'Suggestion 1', 'hook' => 'Hook 1'],
                    ['title' => 'Suggestion 2', 'hook' => 'Hook 2'],
                    ['title' => 'Suggestion 3', 'hook' => 'Hook 3'],
                ],
            ],
            'created_by' => $this->user->id,
        ]);

        // When: Viewing the draft detail page
        $response = $this->actingAs($this->user)
            ->get(route('admin.campaign-drafts.show', $this->draft));

        // Then: All suggestions should be visible
        $response->assertStatus(200);
        $response->assertSee('Suggestion 1');
        $response->assertSee('Hook 1');
        $response->assertSee('Suggestion 2');
        $response->assertSee('Hook 2');
        $response->assertSee('Suggestion 3');
        $response->assertSee('Hook 3');
    }
}
