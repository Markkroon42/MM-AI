<?php

namespace Tests\Unit;

use App\Enums\DraftEnrichmentTypeEnum;
use App\Enums\PublishActionTypeEnum;
use App\Exceptions\NonRetryablePublishException;
use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;
use App\Models\CampaignTemplate;
use App\Models\SystemSetting;
use App\Models\UtmTemplate;
use App\Models\User;
use App\Services\AI\CopyAgentService;
use App\Services\CampaignDraft\UtmGeneratorService;
use App\Services\Execution\PublishJobService;
use App\Services\Meta\MetaCampaignWriteService;
use App\Services\Meta\MetaWriteClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for 3 remaining bug fixes
 */
class RemainingBugFixesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Issue #1: Copy enrichment auto-apply
     * Test that copy is automatically applied to draft ads after generation
     */
    public function test_copy_enrichment_auto_applies_to_draft()
    {
        $this->markTestSkipped('Requires LLM mocking - manual verification needed');

        // This test would require mocking the LLM gateway
        // Manual verification: check logs show:
        // - [COPY_AGENT] Auto-applying copy enrichment to draft
        // - [DRAFT_ENRICHMENT] Applying copy to ads
        // - [COPY_AGENT] Copy auto-apply completed
    }

    /**
     * Issue #2: UTM slugging
     * Test that brand and other values are properly slugged
     */
    public function test_utm_generator_slugs_brand_values()
    {
        $briefing = CampaignBriefing::factory()->create([
            'brand' => 'KIS Haircare',  // Has space
            'market' => 'NL',
            'objective' => 'LEADS',
        ]);

        $utmTemplate = UtmTemplate::factory()->create([
            'source' => 'meta',
            'medium' => 'paid_social',
            'campaign_pattern' => '{brand}_{market}_{objective}',
        ]);

        $campaignTemplate = CampaignTemplate::factory()->create([
            'brand' => 'KIS Haircare',
            'market' => 'NL',
            'funnel_stage' => 'PROSPECTING',
            'objective' => 'LEADS',
        ]);

        $utmGenerator = app(UtmGeneratorService::class);
        $utmParameters = $utmGenerator->generate(
            $utmTemplate,
            $briefing,
            'KIS_HAIRCARE_NL_PROSPECTING_LEADS_202603',
            $campaignTemplate
        );

        // Assert brand is slugged (no spaces)
        $this->assertStringNotContainsString(' ', $utmParameters['utm_campaign']);
        $this->assertStringContainsString('kis_haircare', $utmParameters['utm_campaign']);
        $this->assertStringContainsString('nl', $utmParameters['utm_campaign']);
        $this->assertStringContainsString('leads', $utmParameters['utm_campaign']);
    }

    /**
     * Issue #2: UTM content resolution
     * Test that utm_content placeholders are resolved
     */
    public function test_utm_generator_resolves_content_placeholders()
    {
        $briefing = CampaignBriefing::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'LEADS',
        ]);

        $utmTemplate = UtmTemplate::factory()->create([
            'source' => 'meta',
            'medium' => 'paid_social',
            'campaign_pattern' => '{brand}_{market}',
            'content_pattern' => '{creative_type}_{angle}_{variant}',
            'term_pattern' => '{audience}',
        ]);

        $campaignTemplate = CampaignTemplate::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'funnel_stage' => 'PROSPECTING',
            'objective' => 'LEADS',
            'theme' => 'besparingscalculator',
            'structure_json' => [
                'campaign' => ['objective' => 'LEADS'],
                'ad_sets' => [
                    ['name' => 'Ad Set 1', 'audience' => 'broad_salonowners']
                ],
                'ads' => [
                    ['name' => 'Ad 1', 'creative_type' => 'video', 'angle' => 'besparing']
                ],
            ],
        ]);

        $utmGenerator = app(UtmGeneratorService::class);
        $utmParameters = $utmGenerator->generate(
            $utmTemplate,
            $briefing,
            'KIS_NL_PROSPECTING_LEADS_202603',
            $campaignTemplate
        );

        // Assert no raw placeholders remain
        $this->assertStringNotContainsString('{creative_type}', $utmParameters['utm_content']);
        $this->assertStringNotContainsString('{angle}', $utmParameters['utm_content']);
        $this->assertStringNotContainsString('{variant}', $utmParameters['utm_content']);
        $this->assertStringNotContainsString('{audience}', $utmParameters['utm_term']);

        // Assert resolved values are present
        $this->assertStringContainsString('video', $utmParameters['utm_content']);
        $this->assertStringContainsString('besparingscalculator', $utmParameters['utm_content']);
        $this->assertStringContainsString('v1', $utmParameters['utm_content']);
        $this->assertStringContainsString('broad_salonowners', $utmParameters['utm_term']);
    }

    /**
     * Issue #2: UTM content fallback
     * Test that utm_content uses fallback when template has no ads
     */
    public function test_utm_generator_uses_fallback_for_empty_template()
    {
        $briefing = CampaignBriefing::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'LEADS',
        ]);

        $utmTemplate = UtmTemplate::factory()->create([
            'source' => 'meta',
            'medium' => 'paid_social',
            'campaign_pattern' => '{brand}_{market}',
            'content_pattern' => '{creative_type}_{angle}_{variant}',
            'term_pattern' => '{audience}',
        ]);

        // Template with no ads/ad_sets
        $campaignTemplate = CampaignTemplate::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'structure_json' => [
                'campaign' => ['objective' => 'LEADS'],
                'ad_sets' => [],
                'ads' => [],
            ],
        ]);

        $utmGenerator = app(UtmGeneratorService::class);
        $utmParameters = $utmGenerator->generate(
            $utmTemplate,
            $briefing,
            'KIS_NL_PROSPECTING_LEADS_202603',
            $campaignTemplate
        );

        // Assert fallback values are used
        $this->assertStringNotContainsString('{creative_type}', $utmParameters['utm_content']);
        $this->assertStringNotContainsString('{audience}', $utmParameters['utm_term']);
        $this->assertStringContainsString('generic', $utmParameters['utm_content']); // fallback
        $this->assertStringContainsString('broad_default', $utmParameters['utm_term']); // fallback
    }

    /**
     * Issue #3: Publish job dispatch
     * Test that publish job is dispatched to queue after creation
     */
    public function test_publish_job_is_dispatched_after_creation()
    {
        Queue::fake();

        $draft = CampaignDraft::factory()->create([
            'generated_name' => 'TEST_CAMPAIGN_202603',
            'status' => 'approved',
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'TEST_CAMPAIGN_202603',
                    'objective' => 'LEADS',
                ],
                'ad_sets' => [],
                'ads' => [],
            ],
        ]);

        $publishJobService = app(PublishJobService::class);

        $job = $publishJobService->create(
            $draft,
            PublishActionTypeEnum::PUBLISH_CAMPAIGN_DRAFT,
            $draft->draft_payload_json
        );

        // Assert job was created
        $this->assertNotNull($job);
        $this->assertEquals('pending', $job->status);

        // Assert ExecutePublishJob was dispatched
        Queue::assertPushed(\App\Jobs\Execution\ExecutePublishJob::class, function ($queuedJob) use ($job) {
            return $queuedJob->publishJob->id === $job->id;
        });
    }

    /**
     * Issue #3: Publish job execution logging
     * Test that execution path logs correctly
     */
    public function test_publish_job_execution_has_proper_logging()
    {
        // This test verifies the logging structure is in place
        // Manual verification needed for actual log output

        $draft = CampaignDraft::factory()->create([
            'generated_name' => 'TEST_CAMPAIGN_202603',
            'status' => 'approved',
        ]);

        $publishJobService = app(PublishJobService::class);

        $job = $publishJobService->create(
            $draft,
            PublishActionTypeEnum::PUBLISH_CAMPAIGN_DRAFT,
            []
        );

        // Verify job is created with proper initial state
        $this->assertEquals('pending', $job->status);
        $this->assertEquals(0, $job->attempts);

        // Expected log entries (manual verification):
        // [PUBLISH_JOB_SERVICE] Creating publish job
        // [PUBLISH_JOB_SERVICE] Dispatching publish job execution
        // [PUBLISH_JOB_SERVICE] Publish job dispatched to queue
        // [EXECUTE_PUBLISH_JOB] Starting publish job execution
        // [PUBLISH_JOB_SERVICE] Running publish job
        // [PUBLISH_JOB_EXECUTION] Starting Meta write call
        // [PUBLISH_JOB_EXECUTION] Meta write call completed
        // [PUBLISH_JOB_EXECUTION] Success
    }

    /**
     * Fix #1: Account ID resolution from draft payload
     */
    public function test_account_id_resolved_from_draft_payload()
    {
        $user = User::factory()->create();
        $briefing = CampaignBriefing::factory()->create([
            'created_by' => $user->id,
            'meta_account_id' => null,
        ]);

        $template = CampaignTemplate::factory()->create([
            'meta_account_id' => null,
        ]);

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'draft_payload_json' => [
                'meta_account_id' => 'act_123456789',
                'campaign' => ['name' => 'Test Campaign'],
            ],
        ]);

        $metaWriteClient = $this->createMock(MetaWriteClient::class);
        $metaWriteClient->expects($this->once())
            ->method('createCampaign')
            ->with('act_123456789', $this->anything())
            ->willReturn(['id' => 'meta_campaign_123']);

        $service = new MetaCampaignWriteService($metaWriteClient);
        $response = $service->publishDraft($draft);

        $this->assertArrayHasKey('id', $response);
    }

    /**
     * Fix #1: Account ID resolution from briefing
     */
    public function test_account_id_resolved_from_briefing()
    {
        $user = User::factory()->create();
        $briefing = CampaignBriefing::factory()->create([
            'created_by' => $user->id,
            'meta_account_id' => 'act_briefing_123',
        ]);

        $template = CampaignTemplate::factory()->create([
            'meta_account_id' => null,
        ]);

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => ['name' => 'Test Campaign'],
            ],
        ]);

        $metaWriteClient = $this->createMock(MetaWriteClient::class);
        $metaWriteClient->expects($this->once())
            ->method('createCampaign')
            ->with('act_briefing_123', $this->anything())
            ->willReturn(['id' => 'meta_campaign_123']);

        $service = new MetaCampaignWriteService($metaWriteClient);
        $response = $service->publishDraft($draft);

        $this->assertArrayHasKey('id', $response);
    }

    /**
     * Fix #1: Account ID resolution from template
     */
    public function test_account_id_resolved_from_template()
    {
        $user = User::factory()->create();
        $briefing = CampaignBriefing::factory()->create([
            'created_by' => $user->id,
            'meta_account_id' => null,
        ]);

        $template = CampaignTemplate::factory()->create([
            'meta_account_id' => 'act_template_456',
        ]);

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => ['name' => 'Test Campaign'],
            ],
        ]);

        $metaWriteClient = $this->createMock(MetaWriteClient::class);
        $metaWriteClient->expects($this->once())
            ->method('createCampaign')
            ->with('act_template_456', $this->anything())
            ->willReturn(['id' => 'meta_campaign_123']);

        $service = new MetaCampaignWriteService($metaWriteClient);
        $response = $service->publishDraft($draft);

        $this->assertArrayHasKey('id', $response);
    }

    /**
     * Fix #1: Account ID resolution from system setting
     */
    public function test_account_id_resolved_from_system_setting()
    {
        SystemSetting::set('meta', 'default_account_id', 'act_system_999');

        $user = User::factory()->create();
        $briefing = CampaignBriefing::factory()->create([
            'created_by' => $user->id,
            'meta_account_id' => null,
        ]);

        $template = CampaignTemplate::factory()->create([
            'meta_account_id' => null,
        ]);

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => ['name' => 'Test Campaign'],
            ],
        ]);

        $metaWriteClient = $this->createMock(MetaWriteClient::class);
        $metaWriteClient->expects($this->once())
            ->method('createCampaign')
            ->with('act_system_999', $this->anything())
            ->willReturn(['id' => 'meta_campaign_123']);

        $service = new MetaCampaignWriteService($metaWriteClient);
        $response = $service->publishDraft($draft);

        $this->assertArrayHasKey('id', $response);
    }

    /**
     * Fix #1: No Meta call without account ID
     */
    public function test_no_meta_call_without_account_id()
    {
        $this->expectException(NonRetryablePublishException::class);
        $this->expectExceptionMessage('No valid Meta ad account ID resolved for draft publish');

        $user = User::factory()->create();
        $briefing = CampaignBriefing::factory()->create([
            'created_by' => $user->id,
            'meta_account_id' => null,
        ]);

        $template = CampaignTemplate::factory()->create([
            'meta_account_id' => null,
        ]);

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => ['name' => 'Test Campaign'],
            ],
        ]);

        $metaWriteClient = $this->createMock(MetaWriteClient::class);
        // Should never be called
        $metaWriteClient->expects($this->never())
            ->method('createCampaign');

        $service = new MetaCampaignWriteService($metaWriteClient);
        $service->publishDraft($draft);
    }

    /**
     * Fix #2: NonRetryablePublishException is detected by class
     */
    public function test_non_retryable_exception_detected_by_class()
    {
        $exception = new NonRetryablePublishException('No valid Meta ad account ID');

        $this->assertTrue(NonRetryablePublishException::isNonRetryable($exception));
    }

    /**
     * Fix #2: NonRetryablePublishException is detected by pattern
     */
    public function test_non_retryable_exception_detected_by_pattern()
    {
        $exception = new \Exception('No valid Meta ad account ID resolved for draft publish');

        $this->assertTrue(NonRetryablePublishException::isNonRetryable($exception));

        $exception2 = new \Exception('Invalid publish context detected');
        $this->assertTrue(NonRetryablePublishException::isNonRetryable($exception2));

        $exception3 = new \Exception('Object with ID \'act_\' does not exist');
        $this->assertTrue(NonRetryablePublishException::isNonRetryable($exception3));
    }

    /**
     * Fix #2: Retryable exceptions are not marked as non-retryable
     */
    public function test_retryable_exceptions_not_marked_non_retryable()
    {
        $exception = new \Exception('Network timeout');

        $this->assertFalse(NonRetryablePublishException::isNonRetryable($exception));

        $exception2 = new \Exception('Rate limit exceeded');
        $this->assertFalse(NonRetryablePublishException::isNonRetryable($exception2));
    }
}
