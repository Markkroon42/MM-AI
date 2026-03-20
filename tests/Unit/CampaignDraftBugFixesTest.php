<?php

namespace Tests\Unit;

use App\Enums\DraftEnrichmentTypeEnum;
use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;
use App\Models\CampaignTemplate;
use App\Models\DraftEnrichment;
use App\Models\UtmTemplate;
use App\Services\AI\CopyAgentService;
use App\Services\AI\CreativeSuggestionAgentService;
use App\Services\AI\DraftEnrichmentService;
use App\Services\CampaignBuilder\CampaignReadinessService;
use App\Services\CampaignBuilder\DraftValidationService;
use App\Services\CampaignDraft\CampaignDraftBuilderService;
use App\Services\CampaignDraft\UtmGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for 6 campaign draft bug fixes
 */
class CampaignDraftBugFixesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fix #1: Campaign name source of truth
     * Test that both generated_name and payload['campaign']['name'] are set
     */
    public function test_campaign_name_source_of_truth()
    {
        $briefing = CampaignBriefing::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'LEADS',
        ]);

        $template = CampaignTemplate::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'funnel_stage' => 'PROSPECTING',
            'objective' => 'LEADS',
            'theme' => 'BESPARINGSCALCULATOR',
            'structure_json' => [
                'campaign' => ['objective' => 'LEADS'],
                'ad_sets' => [],
                'ads' => [],
            ],
        ]);

        $builder = app(CampaignDraftBuilderService::class);
        $draft = $builder->buildFromBriefing($briefing, $template);

        // Assert both sources are set
        $this->assertNotEmpty($draft->generated_name);
        $this->assertNotEmpty($draft->draft_payload_json['campaign']['name']);

        // Assert they are equal
        $this->assertEquals(
            $draft->generated_name,
            $draft->draft_payload_json['campaign']['name']
        );
    }

    /**
     * Fix #4: Naming generator theme
     * Test that theme is included in campaign name
     */
    public function test_naming_generator_includes_theme()
    {
        $briefing = CampaignBriefing::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'LEADS',
        ]);

        $template = CampaignTemplate::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'funnel_stage' => 'PROSPECTING',
            'objective' => 'LEADS',
            'theme' => 'BESPARINGSCALCULATOR',
            'structure_json' => [
                'campaign' => ['objective' => 'LEADS'],
                'ad_sets' => [],
                'ads' => [],
            ],
        ]);

        $builder = app(CampaignDraftBuilderService::class);
        $draft = $builder->buildFromBriefing($briefing, $template);

        // Assert theme is in the name
        $this->assertStringContainsString('BESPARINGSCALCULATOR', $draft->generated_name);

        // Expected format: KIS_NL_PROSPECTING_LEADS_BESPARINGSCALCULATOR_202603
        $this->assertMatchesRegularExpression(
            '/^KIS_NL_PROSPECTING_LEADS_BESPARINGSCALCULATOR_\d{6}$/',
            $draft->generated_name
        );
    }

    /**
     * Fix #3: UTM interpolation
     * Test that placeholders are replaced with actual values
     */
    public function test_utm_interpolation()
    {
        $briefing = CampaignBriefing::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'LEADS',
        ]);

        $utmTemplate = UtmTemplate::factory()->create([
            'source' => 'meta',
            'medium' => 'paid_social',
            'campaign_pattern' => '{brand}_{market}_{funnel}_{objective}_{theme}_{yyyymm}',
            'content_pattern' => '{creative_type}_{angle}',
            'term_pattern' => '{audience}',
        ]);

        $campaignTemplate = CampaignTemplate::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'funnel_stage' => 'PROSPECTING',
            'objective' => 'LEADS',
            'theme' => 'BESPARINGSCALCULATOR',
        ]);

        $utmGenerator = app(UtmGeneratorService::class);
        $utmParameters = $utmGenerator->generate(
            $utmTemplate,
            $briefing,
            'KIS_NL_PROSPECTING_LEADS_202603',
            $campaignTemplate
        );

        // Assert no raw placeholders remain
        $this->assertStringNotContainsString('{brand}', $utmParameters['utm_campaign']);
        $this->assertStringNotContainsString('{market}', $utmParameters['utm_campaign']);
        $this->assertStringNotContainsString('{funnel}', $utmParameters['utm_campaign']);
        $this->assertStringNotContainsString('{objective}', $utmParameters['utm_campaign']);
        $this->assertStringNotContainsString('{theme}', $utmParameters['utm_campaign']);

        // Assert values are interpolated
        $this->assertStringContainsString('kis', $utmParameters['utm_campaign']);
        $this->assertStringContainsString('nl', $utmParameters['utm_campaign']);
        $this->assertStringContainsString('prospecting', $utmParameters['utm_campaign']);
        $this->assertStringContainsString('leads', $utmParameters['utm_campaign']);
        $this->assertStringContainsString('besparingscalculator', $utmParameters['utm_campaign']);
    }

    /**
     * Fix #2: Copy enrichment apply
     * Test that copy enrichment is applied to ad payload
     */
    public function test_copy_enrichment_applies_to_ads()
    {
        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => [
                'campaign' => ['name' => 'Test Campaign'],
                'ads' => [
                    [
                        'name' => 'Ad 1',
                        'creative' => [
                            'object_story_spec' => [
                                'link_data' => []
                            ]
                        ]
                    ],
                    [
                        'name' => 'Ad 2',
                        'creative' => [
                            'object_story_spec' => [
                                'link_data' => []
                            ]
                        ]
                    ],
                ],
            ],
        ]);

        $enrichment = DraftEnrichment::factory()->create([
            'campaign_draft_id' => $draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::COPY_VARIANTS->value,
            'payload_json' => [
                'primary_texts' => [
                    'Primary text for ad 1',
                    'Primary text for ad 2',
                ],
                'headlines' => [
                    'Headline 1',
                    'Headline 2',
                ],
                'descriptions' => [
                    'Description 1',
                    'Description 2',
                ],
            ],
        ]);

        $enrichmentService = app(DraftEnrichmentService::class);
        $user = \App\Models\User::factory()->create();
        $enrichmentService->applyEnrichment($enrichment, $user);

        // Refresh draft
        $draft->refresh();

        // Assert copy was applied to ads
        $ads = $draft->draft_payload_json['ads'];
        $this->assertEquals('Primary text for ad 1', $ads[0]['creative']['object_story_spec']['link_data']['message']);
        $this->assertEquals('Primary text for ad 2', $ads[1]['creative']['object_story_spec']['link_data']['message']);
        $this->assertEquals('Headline 1', $ads[0]['creative']['object_story_spec']['link_data']['name']);
        $this->assertEquals('Headline 2', $ads[1]['creative']['object_story_spec']['link_data']['name']);
    }

    /**
     * Fix #2: Copy enrichment with fallback
     * Test that when there are fewer variants than ads, fallback to first variant
     */
    public function test_copy_enrichment_fallback_to_first_variant()
    {
        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => [
                'campaign' => ['name' => 'Test Campaign'],
                'ads' => [
                    ['name' => 'Ad 1', 'creative' => ['object_story_spec' => ['link_data' => []]]],
                    ['name' => 'Ad 2', 'creative' => ['object_story_spec' => ['link_data' => []]]],
                    ['name' => 'Ad 3', 'creative' => ['object_story_spec' => ['link_data' => []]]],
                ],
            ],
        ]);

        $enrichment = DraftEnrichment::factory()->create([
            'campaign_draft_id' => $draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::COPY_VARIANTS->value,
            'payload_json' => [
                'primary_texts' => ['Primary text 1'],
                'headlines' => [],
                'descriptions' => [],
            ],
        ]);

        $enrichmentService = app(DraftEnrichmentService::class);
        $user = \App\Models\User::factory()->create();
        $enrichmentService->applyEnrichment($enrichment, $user);

        $draft->refresh();
        $ads = $draft->draft_payload_json['ads'];

        // All ads should have the first (and only) primary text
        $this->assertEquals('Primary text 1', $ads[0]['creative']['object_story_spec']['link_data']['message']);
        $this->assertEquals('Primary text 1', $ads[1]['creative']['object_story_spec']['link_data']['message']);
        $this->assertEquals('Primary text 1', $ads[2]['creative']['object_story_spec']['link_data']['message']);
    }

    /**
     * Fix #5: Duplicate AI generation prevention
     * Test that duplicate copy generation is prevented
     */
    public function test_duplicate_copy_generation_prevention()
    {
        $this->markTestSkipped('Requires mocking LLM gateway - integration test needed');
    }

    /**
     * Fix #6: Readiness validator alignment
     * Test that readiness and validation use the same source for campaign name
     */
    public function test_readiness_validator_alignment_campaign_name()
    {
        $draft = CampaignDraft::factory()->create([
            'generated_name' => 'TEST_CAMPAIGN_202603',
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'TEST_CAMPAIGN_202603',
                    'objective' => 'LEADS',
                ],
                'ad_sets' => [['name' => 'Ad Set 1']],
                'ads' => [
                    [
                        'name' => 'Ad 1',
                        'creative' => [
                            'object_story_spec' => [
                                'link_data' => [
                                    'message' => 'Test copy'
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ]);

        $readinessService = app(CampaignReadinessService::class);
        $validationService = app(DraftValidationService::class);

        $readiness = $readinessService->calculateReadiness($draft);
        $validation = $validationService->validate($draft);

        // Find campaign name check in readiness
        $campaignNameCheck = collect($readiness['checks'])
            ->firstWhere('name', 'Campaign name');

        $this->assertTrue($campaignNameCheck['passed'], 'Readiness should pass campaign name check');
        $this->assertFalse($validation['has_blockers'], 'Validation should not have blockers for campaign name');
    }

    /**
     * Fix #6: Readiness validator alignment for copy
     * Test that both check the same ad copy structure
     */
    public function test_readiness_validator_alignment_copy()
    {
        $draft = CampaignDraft::factory()->create([
            'generated_name' => 'TEST_CAMPAIGN_202603',
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'TEST_CAMPAIGN_202603',
                    'objective' => 'LEADS',
                ],
                'ad_sets' => [['name' => 'Ad Set 1']],
                'ads' => [
                    [
                        'name' => 'Ad 1',
                        'creative' => [
                            'object_story_spec' => [
                                'link_data' => [
                                    'message' => 'Test primary text'
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ]);

        $readinessService = app(CampaignReadinessService::class);
        $validationService = app(DraftValidationService::class);

        $readiness = $readinessService->calculateReadiness($draft);
        $validation = $validationService->validate($draft);

        // Check copy availability
        $copyCheck = collect($readiness['checks'])
            ->firstWhere('name', 'Copy available');

        $this->assertTrue($copyCheck['passed'], 'Readiness should detect copy is available');

        // Validation should not warn about missing primary text
        $copyWarnings = collect($validation['warnings'])
            ->filter(fn($w) => str_contains($w['message'], 'missing primary text'));

        $this->assertCount(0, $copyWarnings, 'Validation should not warn about missing primary text when copy exists');
    }
}
