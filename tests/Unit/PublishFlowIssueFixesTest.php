<?php

namespace Tests\Unit;

use App\Enums\PublishJobStatusEnum;
use App\Exceptions\NonRetryablePublishException;
use App\Jobs\Execution\ExecutePublishJob;
use App\Models\CampaignDraft;
use App\Models\CampaignTemplate;
use App\Models\PublishJob;
use App\Services\Execution\PublishJobService;
use App\Services\Meta\MetaCampaignWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for publish flow issue fixes:
 * 1. Ad set optimization_goal mapping (LEADS → LEAD_GENERATION)
 * 2. Non-retryable publish execution stop behavior
 */
class PublishFlowIssueFixesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that internal LEADS maps to Meta LEAD_GENERATION for ad sets
     */
    public function test_ad_set_optimization_goal_maps_leads_to_lead_generation()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);
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
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS', // Internal value
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_123'], 200),
            '*/act_12345/adsets' => Http::response(['id' => 'adset_123'], 200),
            '*/act_12345/adcreatives' => Http::response(['id' => 'creative_123'], 200),
            '*/act_12345/ads' => Http::response(['id' => 'ad_123'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);
        $service->publishDraft($draft);

        // Assert that the ad set request was sent with LEAD_GENERATION, not LEADS
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/adsets')) {
                return false;
            }

            $data = $request->data();

            // Must have optimization_goal set to LEAD_GENERATION
            if (!isset($data['optimization_goal'])) {
                return false;
            }

            if ($data['optimization_goal'] !== 'LEAD_GENERATION') {
                return false;
            }

            // Must NOT be LEADS
            return $data['optimization_goal'] !== 'LEADS';
        });
    }

    /**
     * Test that ad set payload does not contain invalid LEADS value
     */
    public function test_ad_set_payload_does_not_contain_invalid_leads_value()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);
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
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test the buildAdSetPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildAdSetPayload');
        $method->setAccessible(true);

        $adSetData = $draft->draft_payload_json['ad_sets'][0];
        $payload = $method->invoke($service, 'campaign_123', $adSetData, $draft, $draft->draft_payload_json);

        // Assert optimization_goal is LEAD_GENERATION, not LEADS
        $this->assertEquals('LEAD_GENERATION', $payload['optimization_goal']);
        $this->assertNotEquals('LEADS', $payload['optimization_goal']);
    }

    /**
     * Test mapping of various optimization goals
     */
    public function test_optimization_goal_mapping_for_various_values()
    {
        $service = app(MetaCampaignWriteService::class);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('mapOptimizationGoalToMeta');
        $method->setAccessible(true);

        // Test LEADS → LEAD_GENERATION
        $this->assertEquals('LEAD_GENERATION', $method->invoke($service, 'LEADS'));

        // Test TRAFFIC → LINK_CLICKS
        $this->assertEquals('LINK_CLICKS', $method->invoke($service, 'TRAFFIC'));

        // Test AWARENESS → REACH
        $this->assertEquals('REACH', $method->invoke($service, 'AWARENESS'));

        // Test already correct value passes through
        $this->assertEquals('LEAD_GENERATION', $method->invoke($service, 'LEAD_GENERATION'));
        $this->assertEquals('LINK_CLICKS', $method->invoke($service, 'LINK_CLICKS'));
    }

    /**
     * Test that invalid optimization goal throws controlled failure
     */
    public function test_invalid_optimization_goal_throws_controlled_failure()
    {
        $service = app(MetaCampaignWriteService::class);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('mapOptimizationGoalToMeta');
        $method->setAccessible(true);

        $this->expectException(NonRetryablePublishException::class);
        $this->expectExceptionMessage('Unknown optimization goal');

        $method->invoke($service, 'INVALID_GOAL_XYZ');
    }

    /**
     * Test that invalid optimization goal prevents Meta API call
     */
    public function test_invalid_optimization_goal_prevents_meta_call()
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
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'INVALID_GOAL', // Invalid value
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_123'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);

        try {
            $service->publishDraft($draft);
            $this->fail('Expected NonRetryablePublishException to be thrown');
        } catch (NonRetryablePublishException $e) {
            $this->assertStringContainsString('Unknown optimization goal', $e->getMessage());
        }

        // Assert no ad set request was made (failed before Meta call)
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/adsets');
        });
    }

    /**
     * Test non-retryable publish execution stops without retry
     */
    public function test_non_retryable_publish_execution_stops_without_retry()
    {
        Queue::fake();

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
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    ['name' => 'Test AdSet', 'optimization_goal' => 'LEADS'],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $job = PublishJob::create([
            'draft_id' => $draft->id,
            'provider' => 'meta',
            'action_type' => 'publish_campaign_draft',
            'payload_json' => [],
            'status' => PublishJobStatusEnum::PENDING->value,
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response([
                'error' => [
                    'message' => '(#100) optimization_goal must be one of the following values',
                    'code' => 100,
                ],
            ], 400),
        ]);

        $executeJob = new ExecutePublishJob($job);
        $publishJobService = app(PublishJobService::class);

        // Execute the job
        $executeJob->handle($publishJobService);

        // Refresh job from database
        $job->refresh();

        // Assert job is marked as failed
        $this->assertEquals(PublishJobStatusEnum::FAILED->value, $job->status);

        // Assert error message is set
        $this->assertNotNull($job->error_message);
        $this->assertStringContainsString('Meta API validation error', $job->error_message);
    }

    /**
     * Test Meta 400 validation error stops execution without requeue
     */
    public function test_meta_400_validation_error_stops_execution()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']); // FIX 3.3: Add page_id so we reach Meta API call

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    ['name' => 'Test AdSet', 'optimization_goal' => 'LEADS'],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $job = PublishJob::create([
            'draft_id' => $draft->id,
            'provider' => 'meta',
            'action_type' => 'publish_campaign_draft',
            'payload_json' => [],
            'status' => PublishJobStatusEnum::PENDING->value,
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_123'], 200),
            '*/act_12345/adsets' => Http::response([
                'error' => [
                    'message' => '(#100) optimization_goal must be one of the following values: LEAD_GENERATION, ...',
                    'code' => 100,
                ],
            ], 400),
        ]);

        $executeJob = new ExecutePublishJob($job);
        $publishJobService = app(PublishJobService::class);

        // Execute the job - should NOT throw exception (controlled stop)
        $executeJob->handle($publishJobService);

        // Refresh job
        $job->refresh();

        // Assert job failed
        $this->assertEquals(PublishJobStatusEnum::FAILED->value, $job->status);

        // Assert error contains validation error message
        $this->assertStringContainsString('Meta API validation error (400)', $job->error_message);
    }

    /**
     * Test happy path with valid optimization goal mapping
     */
    public function test_happy_path_ad_set_with_valid_optimization_goal()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);
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
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'BROAD_SALONOWNERS_LEADS',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    [
                        'name' => 'VIDEO_BESPARING_V1',
                        'message' => 'Test message',
                        'headline' => 'Test headline',
                    ],
                ],
            ],
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_123'], 200),
            '*/act_12345/adsets' => Http::response(['id' => 'adset_123'], 200),
            '*/act_12345/adcreatives' => Http::response(['id' => 'creative_123'], 200),
            '*/act_12345/ads' => Http::response(['id' => 'ad_123'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);
        $result = $service->publishDraft($draft);

        // Assert campaign created
        $this->assertArrayHasKey('campaign', $result);
        $this->assertEquals('campaign_123', $result['campaign']['id']);

        // Assert ad set created
        $this->assertArrayHasKey('ad_sets', $result);
        $this->assertCount(1, $result['ad_sets']);
        $this->assertEquals('adset_123', $result['ad_sets'][0]['id']);

        // Verify optimization_goal was LEAD_GENERATION in request
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/adsets')) {
                return false;
            }

            $data = $request->data();
            return isset($data['optimization_goal'])
                && $data['optimization_goal'] === 'LEAD_GENERATION';
        });
    }

    /**
     * FIX 1: Test ad set payload contains bid_strategy without bid_amount (for non-LEAD_GENERATION)
     */
    public function test_ad_set_payload_contains_bid_strategy_without_bid_amount()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);
        config(['meta.pixel_id' => 'pixel_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'TRAFFIC',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'TRAFFIC',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test the buildAdSetPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildAdSetPayload');
        $method->setAccessible(true);

        $adSetData = $draft->draft_payload_json['ad_sets'][0];
        $payload = $method->invoke($service, 'campaign_123', $adSetData, $draft, $draft->draft_payload_json);

        // Assert bid_strategy is set to LOWEST_COST_WITHOUT_CAP (for non-LEAD_GENERATION)
        $this->assertEquals('LOWEST_COST_WITHOUT_CAP', $payload['bid_strategy']);

        // Assert bid_amount is NOT present (would require bid_amount)
        $this->assertArrayNotHasKey('bid_amount', $payload);
    }

    /**
     * FIX 1: Test ad set payload sent to Meta contains valid bidding config (for non-LEAD_GENERATION)
     */
    public function test_ad_set_payload_sent_to_meta_contains_valid_bidding_config()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);
        config(['meta.pixel_id' => 'pixel_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'TRAFFIC',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'TRAFFIC',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_123'], 200),
            '*/act_12345/adsets' => Http::response(['id' => 'adset_123'], 200),
            '*/act_12345/adcreatives' => Http::response(['id' => 'creative_123'], 200),
            '*/act_12345/ads' => Http::response(['id' => 'ad_123'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);
        $service->publishDraft($draft);

        // Assert that the ad set request contains bid_strategy and no bid_amount (for non-LEAD_GENERATION)
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/adsets')) {
                return false;
            }

            $data = $request->data();

            // Must have bid_strategy (for non-LEAD_GENERATION)
            if (!isset($data['bid_strategy'])) {
                return false;
            }

            // Must be LOWEST_COST_WITHOUT_CAP
            if ($data['bid_strategy'] !== 'LOWEST_COST_WITHOUT_CAP') {
                return false;
            }

            // Must NOT have bid_amount
            if (isset($data['bid_amount'])) {
                return false;
            }

            return true;
        });
    }

    /**
     * FIX 2: Test idempotency guard prevents duplicate campaign creation when job already succeeded
     */
    public function test_idempotency_guard_prevents_duplicate_campaign_when_job_succeeded()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);
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
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        // Create a successful publish job with campaign result
        $existingJob = PublishJob::create([
            'draft_id' => $draft->id,
            'provider' => 'meta',
            'action_type' => 'publish_campaign_draft',
            'payload_json' => [],
            'status' => PublishJobStatusEnum::SUCCESS->value,
            'response_json' => [
                'campaign' => ['id' => 'existing_campaign_123'],
                'ad_sets' => [['id' => 'existing_adset_123']],
            ],
            'executed_at' => now(),
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'new_campaign_456'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);
        $result = $service->publishDraft($draft);

        // Assert existing campaign is reused
        $this->assertEquals('existing_campaign_123', $result['campaign']['id']);

        // Assert NO new campaign was created via Meta API
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/campaigns');
        });
    }

    /**
     * FIX 2: Test idempotency guard skips execution when job status is success
     */
    public function test_idempotency_guard_skips_execution_when_job_status_success()
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
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    ['name' => 'Test AdSet', 'optimization_goal' => 'LEADS'],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        // Job already succeeded
        $job = PublishJob::create([
            'draft_id' => $draft->id,
            'provider' => 'meta',
            'action_type' => 'publish_campaign_draft',
            'payload_json' => [],
            'status' => PublishJobStatusEnum::SUCCESS->value,
            'response_json' => ['campaign' => ['id' => 'campaign_123']],
            'executed_at' => now()->subMinutes(5),
        ]);

        Http::fake();

        $executeJob = new ExecutePublishJob($job);
        $publishJobService = app(PublishJobService::class);

        // Execute the job
        $executeJob->handle($publishJobService);

        // Assert NO Meta API calls were made
        Http::assertNothingSent();
    }

    /**
     * FIX 2: Test idempotency guard skips execution when job status is failed
     */
    public function test_idempotency_guard_skips_execution_when_job_status_failed()
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
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    ['name' => 'Test AdSet', 'optimization_goal' => 'LEADS'],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        // Job already permanently failed
        $job = PublishJob::create([
            'draft_id' => $draft->id,
            'provider' => 'meta',
            'action_type' => 'publish_campaign_draft',
            'payload_json' => [],
            'status' => PublishJobStatusEnum::FAILED->value,
            'error_message' => 'Previous permanent failure',
            'executed_at' => now()->subMinutes(5),
        ]);

        Http::fake();

        $executeJob = new ExecutePublishJob($job);
        $publishJobService = app(PublishJobService::class);

        // Execute the job
        $executeJob->handle($publishJobService);

        // Assert NO Meta API calls were made
        Http::assertNothingSent();
    }

    /**
     * FIX 2: Test duplicate execution does not create second campaign
     */
    public function test_duplicate_execution_does_not_create_second_campaign()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);
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
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        // First execution - create campaign
        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_first'], 200),
            '*/act_12345/adsets' => Http::response(['id' => 'adset_first'], 200),
            '*/act_12345/adcreatives' => Http::response(['id' => 'creative_first'], 200),
            '*/act_12345/ads' => Http::response(['id' => 'ad_first'], 200),
        ]);

        $job = PublishJob::create([
            'draft_id' => $draft->id,
            'provider' => 'meta',
            'action_type' => 'publish_campaign_draft',
            'payload_json' => [],
            'status' => PublishJobStatusEnum::PENDING->value,
        ]);

        $publishJobService = app(PublishJobService::class);
        $publishJobService->run($job);

        // Refresh job
        $job->refresh();
        $this->assertEquals(PublishJobStatusEnum::SUCCESS->value, $job->status);
        $this->assertEquals('campaign_first', $job->response_json['campaign']['id']);

        // Reset HTTP fake to track second execution
        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_second'], 200),
        ]);

        // Second execution - should reuse existing campaign
        $service = app(MetaCampaignWriteService::class);
        $result = $service->publishDraft($draft);

        // Assert existing campaign is reused
        $this->assertEquals('campaign_first', $result['campaign']['id']);

        // Assert NO second campaign create was made
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/campaigns');
        });
    }

    /**
     * FIX 3: Test LEAD_GENERATION ad set contains page_id in promoted_object
     */
    public function test_lead_generation_ad_set_contains_page_id_in_promoted_object()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test the buildAdSetPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildAdSetPayload');
        $method->setAccessible(true);

        $adSetData = $draft->draft_payload_json['ad_sets'][0];
        $payload = $method->invoke($service, 'campaign_123', $adSetData, $draft, $draft->draft_payload_json);

        // FIX 3.2: Assert promoted_object contains page_id for LEAD_GENERATION
        $this->assertArrayHasKey('promoted_object', $payload);
        $this->assertArrayHasKey('page_id', $payload['promoted_object']);
        $this->assertEquals('page_12345', $payload['promoted_object']['page_id']);

        // FIX 3.4: Assert promoted_object contains lead_gen_form_id for LEAD_GENERATION
        $this->assertArrayHasKey('lead_gen_form_id', $payload['promoted_object']);
        $this->assertEquals('form_12345', $payload['promoted_object']['lead_gen_form_id']);
    }

    /**
     * FIX 3.2: Test LEAD_GENERATION payload sent to Meta contains page_id in promoted_object
     */
    public function test_lead_generation_payload_sent_to_meta_contains_page_id()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_123'], 200),
            '*/act_12345/adsets' => Http::response(['id' => 'adset_123'], 200),
            '*/act_12345/adcreatives' => Http::response(['id' => 'creative_123'], 200),
            '*/act_12345/ads' => Http::response(['id' => 'ad_123'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);
        $service->publishDraft($draft);

        // FIX 3.2: Assert that the ad set request contains promoted_object with page_id
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/adsets')) {
                return false;
            }

            $data = $request->data();

            // Must have LEAD_GENERATION optimization_goal
            if (!isset($data['optimization_goal']) || $data['optimization_goal'] !== 'LEAD_GENERATION') {
                return false;
            }

            // Must have promoted_object with page_id
            if (!isset($data['promoted_object'])) {
                return false;
            }

            if (!isset($data['promoted_object']['page_id']) || $data['promoted_object']['page_id'] !== 'page_12345') {
                return false;
            }

            // FIX 3.4: Must have lead_gen_form_id
            if (!isset($data['promoted_object']['lead_gen_form_id']) || $data['promoted_object']['lead_gen_form_id'] !== 'form_12345') {
                return false;
            }

            return true;
        });
    }

    /**
     * FIX 3: Test LEAD_GENERATION payload is Meta-compatible
     */
    public function test_lead_generation_payload_is_meta_compatible()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test the buildAdSetPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildAdSetPayload');
        $method->setAccessible(true);

        $adSetData = $draft->draft_payload_json['ad_sets'][0];
        $payload = $method->invoke($service, 'campaign_123', $adSetData, $draft, $draft->draft_payload_json);

        // Assert Meta-compatible configuration for LEAD_GENERATION
        $this->assertEquals('LEAD_GENERATION', $payload['optimization_goal']);
        $this->assertEquals('IMPRESSIONS', $payload['billing_event']);

        // FIX 3.1: LEAD_GENERATION should NOT have bid_strategy (uses Meta automatic bidding)
        $this->assertArrayNotHasKey('bid_strategy', $payload);
        $this->assertArrayNotHasKey('bid_amount', $payload);

        // FIX 3.2: LEAD_GENERATION should have promoted_object with page_id
        $this->assertArrayHasKey('promoted_object', $payload);
        $this->assertArrayHasKey('page_id', $payload['promoted_object']);

        // FIX 3.4: LEAD_GENERATION should have lead_gen_form_id in promoted_object
        $this->assertArrayHasKey('lead_gen_form_id', $payload['promoted_object']);
    }

    /**
     * FIX 3: Test LEAD_GENERATION happy path with valid Meta configuration
     */
    public function test_lead_generation_happy_path_with_valid_meta_configuration()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Lead Gen Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'BROAD_SALONOWNERS_LEADS',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    [
                        'name' => 'VIDEO_BESPARING_V1',
                        'message' => 'Test message',
                        'headline' => 'Test headline',
                    ],
                ],
            ],
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_123'], 200),
            '*/act_12345/adsets' => Http::response(['id' => 'adset_123'], 200),
            '*/act_12345/adcreatives' => Http::response(['id' => 'creative_123'], 200),
            '*/act_12345/ads' => Http::response(['id' => 'ad_123'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);
        $result = $service->publishDraft($draft);

        // Assert campaign created
        $this->assertArrayHasKey('campaign', $result);
        $this->assertEquals('campaign_123', $result['campaign']['id']);

        // Assert ad set created
        $this->assertArrayHasKey('ad_sets', $result);
        $this->assertCount(1, $result['ad_sets']);
        $this->assertEquals('adset_123', $result['ad_sets'][0]['id']);

        // Verify the payload sent to Meta was valid
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/adsets')) {
                return false;
            }

            $data = $request->data();

            // FIX 3.1 & 3.2: Verify LEAD_GENERATION uses automatic bidding and page_id
            return $data['optimization_goal'] === 'LEAD_GENERATION'
                && $data['billing_event'] === 'IMPRESSIONS'
                && !isset($data['bid_strategy']) // No explicit bid_strategy for LEAD_GENERATION
                && !isset($data['bid_amount'])
                && isset($data['promoted_object']) // Has promoted_object with page_id
                && isset($data['promoted_object']['page_id']);
        });
    }

    /**
     * FIX 3.1: Test non-LEAD_GENERATION still uses explicit bid_strategy
     */
    public function test_non_lead_generation_uses_explicit_bid_strategy()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'TRAFFIC',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'TRAFFIC',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test the buildAdSetPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildAdSetPayload');
        $method->setAccessible(true);

        $adSetData = $draft->draft_payload_json['ad_sets'][0];
        $payload = $method->invoke($service, 'campaign_123', $adSetData, $draft, $draft->draft_payload_json);

        // Assert non-LEAD_GENERATION has explicit bid_strategy
        $this->assertEquals('LINK_CLICKS', $payload['optimization_goal']);
        $this->assertEquals('LOWEST_COST_WITHOUT_CAP', $payload['bid_strategy']);
        $this->assertArrayNotHasKey('bid_amount', $payload);
    }

    /**
     * FIX 3.1: Test LEAD_GENERATION does not have bid_amount requirement
     */
    public function test_lead_generation_does_not_require_bid_amount()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_12345']);
        config(['meta.lead_gen_form_id' => 'form_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_123'], 200),
            '*/act_12345/adsets' => Http::response(['id' => 'adset_123'], 200),
            '*/act_12345/adcreatives' => Http::response(['id' => 'creative_123'], 200),
            '*/act_12345/ads' => Http::response(['id' => 'ad_123'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);
        $service->publishDraft($draft);

        // Assert that the ad set request does NOT contain bid_strategy or bid_amount
        // but DOES contain promoted_object with page_id
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/adsets')) {
                return false;
            }

            $data = $request->data();

            // Must NOT have bid_strategy (automatic bidding)
            if (isset($data['bid_strategy'])) {
                return false;
            }

            // Must NOT have bid_amount
            if (isset($data['bid_amount'])) {
                return false;
            }

            // Must have LEAD_GENERATION
            if ($data['optimization_goal'] !== 'LEAD_GENERATION') {
                return false;
            }

            // FIX 3.2: Must have promoted_object with page_id
            if (!isset($data['promoted_object']) || !isset($data['promoted_object']['page_id'])) {
                return false;
            }

            return true;
        });
    }

    /**
     * FIX 3.3: Test page_id resolution from config
     */
    public function test_page_id_resolution_from_config()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_from_config']);
        config(['meta.lead_gen_form_id' => 'form_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test the buildAdSetPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildAdSetPayload');
        $method->setAccessible(true);

        $adSetData = $draft->draft_payload_json['ad_sets'][0];
        $payload = $method->invoke($service, 'campaign_123', $adSetData, $draft, $draft->draft_payload_json);

        // Assert page_id was resolved from config
        $this->assertArrayHasKey('promoted_object', $payload);
        $this->assertArrayHasKey('page_id', $payload['promoted_object']);
        $this->assertEquals('page_from_config', $payload['promoted_object']['page_id']);
    }

    /**
     * FIX 3.3: Test page_id resolution from draft payload
     */
    public function test_page_id_resolution_from_draft_payload()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_from_config_fallback']);
        config(['meta.lead_gen_form_id' => 'form_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                    'page_id' => 'page_from_draft_payload', // Higher priority than config
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test the buildAdSetPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildAdSetPayload');
        $method->setAccessible(true);

        $adSetData = $draft->draft_payload_json['ad_sets'][0];
        $payload = $method->invoke($service, 'campaign_123', $adSetData, $draft, $draft->draft_payload_json);

        // Assert page_id was resolved from draft payload (higher priority)
        $this->assertArrayHasKey('promoted_object', $payload);
        $this->assertArrayHasKey('page_id', $payload['promoted_object']);
        $this->assertEquals('page_from_draft_payload', $payload['promoted_object']['page_id']);
    }

    /**
     * FIX 3.3: Test page_id resolution from ad set data (highest priority)
     */
    public function test_page_id_resolution_from_ad_set_data()
    {
        config(['meta.default_account_id' => 'act_12345']);
        config(['meta.page_id' => 'page_from_config_fallback']);
        config(['meta.lead_gen_form_id' => 'form_12345']);

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                    'page_id' => 'page_from_draft_fallback',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                        'page_id' => 'page_from_ad_set_data', // Highest priority
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test the buildAdSetPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildAdSetPayload');
        $method->setAccessible(true);

        $adSetData = $draft->draft_payload_json['ad_sets'][0];
        $payload = $method->invoke($service, 'campaign_123', $adSetData, $draft, $draft->draft_payload_json);

        // Assert page_id was resolved from ad set data (highest priority)
        $this->assertArrayHasKey('promoted_object', $payload);
        $this->assertArrayHasKey('page_id', $payload['promoted_object']);
        $this->assertEquals('page_from_ad_set_data', $payload['promoted_object']['page_id']);
    }

    /**
     * FIX 3.3: Test controlled failure when no page_id is available
     */
    public function test_controlled_failure_when_no_page_id_available()
    {
        config(['meta.default_account_id' => 'act_12345']);
        // No page_id in config

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                    // No page_id in draft payload
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                        // No page_id in ad set data
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test the buildAdSetPayload method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('buildAdSetPayload');
        $method->setAccessible(true);

        $this->expectException(NonRetryablePublishException::class);
        $this->expectExceptionMessage('LEAD_GENERATION publish requires a valid page_id');

        $adSetData = $draft->draft_payload_json['ad_sets'][0];
        $method->invoke($service, 'campaign_123', $adSetData, $draft, $draft->draft_payload_json);
    }

    /**
     * FIX 3.3: Test controlled failure prevents Meta API call
     */
    public function test_controlled_failure_prevents_meta_api_call_without_page_id()
    {
        config(['meta.default_account_id' => 'act_12345']);
        // No page_id configured

        $template = CampaignTemplate::factory()->create([
            'landing_page_url' => 'https://example.com/',
        ]);

        $draft = CampaignDraft::factory()->create([
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'landing_page_url' => 'https://example.com/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'Test AdSet',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    ['name' => 'Test Ad', 'message' => 'Test'],
                ],
            ],
        ]);

        Http::fake([
            '*/act_12345/campaigns' => Http::response(['id' => 'campaign_123'], 200),
            '*/act_12345/adsets' => Http::response(['id' => 'adset_123'], 200),
        ]);

        $service = app(MetaCampaignWriteService::class);

        try {
            $service->publishDraft($draft);
            $this->fail('Expected NonRetryablePublishException to be thrown');
        } catch (NonRetryablePublishException $e) {
            $this->assertStringContainsString('LEAD_GENERATION publish requires a valid page_id', $e->getMessage());
        }

        // Assert ad set request was NEVER made (failed before Meta call)
        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), '/adsets');
        });
    }
}
