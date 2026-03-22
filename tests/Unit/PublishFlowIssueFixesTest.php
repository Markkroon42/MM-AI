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
     * FIX 1: Test ad set payload contains bid_strategy without bid_amount
     */
    public function test_ad_set_payload_contains_bid_strategy_without_bid_amount()
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

        // Assert bid_strategy is set to LOWEST_COST_WITHOUT_CAP
        $this->assertEquals('LOWEST_COST_WITHOUT_CAP', $payload['bid_strategy']);

        // Assert bid_amount is NOT present (would require bid_amount)
        $this->assertArrayNotHasKey('bid_amount', $payload);
    }

    /**
     * FIX 1: Test ad set payload sent to Meta contains valid bidding config
     */
    public function test_ad_set_payload_sent_to_meta_contains_valid_bidding_config()
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

        // Assert that the ad set request contains bid_strategy and no bid_amount
        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), '/adsets')) {
                return false;
            }

            $data = $request->data();

            // Must have bid_strategy
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
}
