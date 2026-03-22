<?php

namespace Tests\Unit;

use App\Enums\PublishJobStatusEnum;
use App\Exceptions\NonRetryablePublishException;
use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;
use App\Models\CampaignTemplate;
use App\Models\PublishJob;
use App\Services\Execution\PublishJobService;
use App\Services\Meta\MetaCampaignWriteService;
use App\Services\Meta\MetaWriteClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for full publish flow (campaign → ad sets → creatives → ads)
 */
class FullPublishFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test full happy path publish flow with ad sets and ads
     */
    public function test_full_publish_flow_creates_campaign_adsets_and_ads()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.pixel_id' => 'pixel_12345']);

        $briefing = CampaignBriefing::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'LEADS',
            'budget_amount' => 50.00,
        ]);

        $template = CampaignTemplate::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'leads',
            'landing_page_url' => 'https://besparing.kis-haircare.nl/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'generated_name' => 'KIS_NL_PROSPECTING_LEADS_202603',
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'KIS_NL_PROSPECTING_LEADS_202603',
                    'objective' => 'LEADS',
                    'daily_budget' => 50.00,
                    'status' => 'PAUSED',
                    'landing_page_url' => 'https://besparing.kis-haircare.nl/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'BROAD_SALONOWNERS_LEADS',
                        'audience' => 'broad_salonowners',
                        'optimization_goal' => 'LEADS',
                    ],
                    [
                        'name' => 'INTEREST_HAIRPROFESSIONALS_LEADS',
                        'audience' => 'interest_hairprofessionals',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    [
                        'name' => 'VIDEO_BESPARING_V1',
                        'creative_type' => 'video',
                        'angle' => 'besparing',
                        'message' => 'Ontdek je financiële voordeel binnen 1 minuut',
                        'headline' => 'Bereken je voordeel',
                        'description' => 'Hoeveel kun je besparen op haarverf?',
                        'landing_page_url' => 'https://besparing.kis-haircare.nl/',
                        'utm_parameters' => [
                            'utm_source' => 'meta',
                            'utm_medium' => 'paid_social',
                            'utm_campaign' => 'kis_nl_prospecting_leads_202603',
                            'utm_content' => 'video_besparing_v1',
                        ],
                    ],
                ],
            ],
        ]);

        // Mock Meta API responses
        Http::fake([
            '*/act_12345/campaigns' => Http::response([
                'id' => 'campaign_123',
            ], 200),
            '*/act_12345/adsets' => Http::sequence()
                ->push(['id' => 'adset_1'], 200)
                ->push(['id' => 'adset_2'], 200),
            '*/act_12345/adcreatives' => Http::sequence()
                ->push(['id' => 'creative_1'], 200)
                ->push(['id' => 'creative_2'], 200),
            '*/act_12345/ads' => Http::sequence()
                ->push(['id' => 'ad_1'], 200)
                ->push(['id' => 'ad_2'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);
        $result = $service->publishDraft($draft);

        // Assert campaign created
        $this->assertArrayHasKey('campaign', $result);
        $this->assertEquals('campaign_123', $result['campaign']['id']);

        // Assert 2 ad sets created
        $this->assertArrayHasKey('ad_sets', $result);
        $this->assertCount(2, $result['ad_sets']);
        $this->assertEquals('adset_1', $result['ad_sets'][0]['id']);
        $this->assertEquals('adset_2', $result['ad_sets'][1]['id']);

        // Assert 2 creatives created (1 ad × 2 ad sets)
        $this->assertArrayHasKey('creatives', $result);
        $this->assertCount(2, $result['creatives']);

        // Assert 2 ads created
        $this->assertArrayHasKey('ads', $result);
        $this->assertCount(2, $result['ads']);
    }

    /**
     * Test validation fails when no ad sets
     */
    public function test_validation_fails_without_ad_sets()
    {
        config(['meta.default_account_id' => 'act_12345']);

        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com',
                ],
                'ad_sets' => [], // Empty
                'ads' => [
                    ['name' => 'Ad 1', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        $this->expectException(NonRetryablePublishException::class);
        $this->expectExceptionMessage('Cannot publish campaign without ad sets');

        $service->publishDraft($draft);
    }

    /**
     * Test validation fails when no ads
     */
    public function test_validation_fails_without_ads()
    {
        config(['meta.default_account_id' => 'act_12345']);

        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com',
                ],
                'ad_sets' => [
                    ['name' => 'AdSet 1', 'optimization_goal' => 'LEADS'],
                ],
                'ads' => [], // Empty
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        $this->expectException(NonRetryablePublishException::class);
        $this->expectExceptionMessage('Cannot publish campaign without ads');

        $service->publishDraft($draft);
    }

    /**
     * Test validation fails when no landing page URL
     */
    public function test_validation_fails_without_landing_page_url()
    {
        config(['meta.default_account_id' => 'act_12345']);

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => null,
            'template_id' => null,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    // No landing_page_url
                ],
                'ad_sets' => [
                    ['name' => 'AdSet 1', 'optimization_goal' => 'LEADS'],
                ],
                'ads' => [
                    ['name' => 'Ad 1', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        $this->expectException(NonRetryablePublishException::class);
        $this->expectExceptionMessage('No landing page URL available for publish');

        $service->publishDraft($draft);
    }

    /**
     * Test UTM parameters are included in destination URL
     */
    public function test_utm_parameters_included_in_destination_url()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);

        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    ['name' => 'AdSet 1', 'optimization_goal' => 'LEADS'],
                ],
                'ads' => [
                    [
                        'name' => 'Ad 1',
                        'message' => 'Test message',
                        'headline' => 'Test headline',
                        'landing_page_url' => 'https://example.com/',
                        'utm_parameters' => [
                            'utm_source' => 'meta',
                            'utm_medium' => 'paid_social',
                            'utm_campaign' => 'test_campaign',
                            'utm_content' => 'ad_1',
                        ],
                    ],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildCreativePayload');
        $method->setAccessible(true);

        $adData = $draft->draft_payload_json['ads'][0];
        $creativePayload = $method->invoke($service, $adData, $draft, $draft->draft_payload_json);

        $destinationUrl = $creativePayload['object_story_spec']['link_data']['link'];

        // Assert UTM parameters are in the URL
        $this->assertStringContainsString('utm_source=meta', $destinationUrl);
        $this->assertStringContainsString('utm_medium=paid_social', $destinationUrl);
        $this->assertStringContainsString('utm_campaign=test_campaign', $destinationUrl);
        $this->assertStringContainsString('utm_content=ad_1', $destinationUrl);
    }

    /**
     * Test creative payload includes ad copy
     */
    public function test_creative_payload_includes_ad_copy()
    {
        config(['meta.page_id' => 'page_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                ],
            ],
        ]);

        $adData = [
            'name' => 'Test Ad',
            'message' => 'Primary message text',
            'headline' => 'Ad headline',
            'description' => 'Ad description',
        ];

        $service = app(MetaCampaignWriteService::class);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildCreativePayload');
        $method->setAccessible(true);

        $creativePayload = $method->invoke($service, $adData, $draft, $draft->draft_payload_json);

        $this->assertEquals('Primary message text', $creativePayload['object_story_spec']['link_data']['message']);
        $this->assertEquals('Ad headline', $creativePayload['object_story_spec']['link_data']['name']);
        $this->assertEquals('Ad description', $creativePayload['object_story_spec']['link_data']['description']);
    }

    /**
     * Test ad set payload includes campaign ID and budget
     */
    public function test_ad_set_payload_includes_campaign_id_and_budget()
    {
        config(['meta.pixel_id' => 'pixel_12345']);

        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'daily_budget' => 50.00,
                ],
            ],
        ]);

        $adSetData = [
            'name' => 'Test AdSet',
            'optimization_goal' => 'LEADS',
        ];

        $service = app(MetaCampaignWriteService::class);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildAdSetPayload');
        $method->setAccessible(true);

        $adSetPayload = $method->invoke($service, 'campaign_123', $adSetData, $draft, $draft->draft_payload_json);

        $this->assertEquals('campaign_123', $adSetPayload['campaign_id']);
        $this->assertEquals('Test AdSet', $adSetPayload['name']);
        $this->assertEquals('PAUSED', $adSetPayload['status']);
        $this->assertEquals('LEAD_GENERATION', $adSetPayload['optimization_goal']); // Fix: LEADS maps to LEAD_GENERATION
        $this->assertEquals(5000, $adSetPayload['daily_budget']); // 50.00 * 100
    }

    /**
     * Test duplicate execution protection
     */
    public function test_duplicate_execution_protection()
    {
        config(['meta.default_account_id' => 'act_12345']);

        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => [
                'campaign' => ['name' => 'Test'],
                'ad_sets' => [['name' => 'AdSet 1']],
                'ads' => [['name' => 'Ad 1']],
            ],
        ]);

        $job = PublishJob::create([
            'draft_id' => $draft->id,
            'provider' => 'meta',
            'action_type' => 'publish_campaign_draft',
            'payload_json' => [],
            'status' => PublishJobStatusEnum::SUCCESS->value,
            'executed_at' => now(),
        ]);

        $service = app(PublishJobService::class);
        $result = $service->run($job);

        // Should return job without executing
        $this->assertEquals(PublishJobStatusEnum::SUCCESS->value, $result->status);

        // HTTP should not be called (no requests)
        Http::assertNothingSent();
    }

    /**
     * Test all entities created with PAUSED status
     */
    public function test_all_entities_created_with_paused_status()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.pixel_id' => 'pixel_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'status' => 'draft', // Internal status
                ],
                'ad_sets' => [
                    ['name' => 'AdSet 1', 'optimization_goal' => 'LEADS'],
                ],
                'ads' => [
                    ['name' => 'Ad 1', 'message' => 'Test'],
                ],
            ],
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_123'], 200),
            '*/act_12345/adsets' => Http::response(['id' => 'adset_1'], 200),
            '*/act_12345/adcreatives' => Http::response(['id' => 'creative_1'], 200),
            '*/act_12345/ads' => Http::response(['id' => 'ad_1'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);
        $result = $service->publishDraft($draft);

        // Check that PAUSED status was sent to Meta
        Http::assertSent(function ($request) {
            $data = $request->data();
            // Campaign should be PAUSED
            return isset($data['status']) && $data['status'] === 'PAUSED';
        });
    }

    /**
     * Test campaign creation failure stops the flow
     */
    public function test_campaign_creation_failure_stops_flow()
    {
        config(['meta.default_account_id' => 'act_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                ],
                'ad_sets' => [
                    ['name' => 'AdSet 1', 'optimization_goal' => 'LEADS'],
                ],
                'ads' => [
                    ['name' => 'Ad 1', 'message' => 'Test'],
                ],
            ],
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response([
                'error' => [
                    'message' => 'Campaign creation failed',
                    'code' => 100,
                ],
            ], 400),
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Expect NonRetryablePublishException due to 400 error
        try {
            $service->publishDraft($draft);
            $this->fail('Expected NonRetryablePublishException to be thrown');
        } catch (NonRetryablePublishException $e) {
            // Success - the exception was thrown
            $this->assertStringContainsString('Meta API validation error (400)', $e->getMessage());
        }

        // Ad sets and ads should not be created
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/adsets');
        });
    }
}
