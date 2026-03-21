<?php

namespace Tests\Unit;

use App\Exceptions\NonRetryablePublishException;
use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;
use App\Models\CampaignTemplate;
use App\Models\User;
use App\Services\Meta\MetaCampaignWriteService;
use App\Services\Meta\MetaWriteClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for publish flow fixes
 */
class PublishFlowFixesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fix #1: Account ID resolution
     * Test that account ID is properly resolved with fallback chain
     */
    public function test_account_id_resolution_from_config()
    {
        // Set config default
        config(['meta.default_account_id' => 'act_12345']);

        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('resolveAccountId');
        $method->setAccessible(true);

        $accountId = $method->invoke($service, $draft, $draft->draft_payload_json);

        $this->assertEquals('act_12345', $accountId);
    }

    /**
     * Fix #1: Account ID validation
     * Test that empty account ID throws non-retryable exception
     */
    public function test_empty_account_id_throws_non_retryable_exception()
    {
        // No account ID in any source
        config(['meta.default_account_id' => null]);

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => null,
            'template_id' => null,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        $this->expectException(NonRetryablePublishException::class);
        $this->expectExceptionMessage('No valid Meta ad account ID resolved');

        $service->publishDraft($draft);
    }

    /**
     * Fix #2 & #3: Payload mapping from campaign structure
     * Test that draft payload is correctly mapped to Meta format
     */
    public function test_payload_mapping_uses_campaign_structure()
    {
        $draft = CampaignDraft::factory()->create([
            'generated_name' => 'FALLBACK_NAME',
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'KIS_NL_PROSPECTING_LEADS_202603',
                    'objective' => 'LEADS',
                    'daily_budget' => 50.00,
                    'status' => 'PAUSED',
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('translateDraftToMetaFormat');
        $method->setAccessible(true);

        $metaPayload = $method->invoke($service, $draft, $draft->draft_payload_json);

        // Assert correct campaign.name is used (not fallback)
        $this->assertEquals('KIS_NL_PROSPECTING_LEADS_202603', $metaPayload['name']);

        // Assert objective is mapped correctly
        $this->assertEquals('OUTCOME_LEADS', $metaPayload['objective']);

        // Assert budget is converted to cents
        $this->assertEquals(5000, $metaPayload['daily_budget']); // 50.00 * 100
    }

    /**
     * Fix #3: Name fallback chain
     * Test that name uses proper fallback when campaign.name missing
     */
    public function test_name_fallback_to_generated_name()
    {
        $draft = CampaignDraft::factory()->create([
            'generated_name' => 'GENERATED_FALLBACK_NAME',
            'draft_payload_json' => [
                'campaign' => [
                    'objective' => 'TRAFFIC',
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('translateDraftToMetaFormat');
        $method->setAccessible(true);

        $metaPayload = $method->invoke($service, $draft, $draft->draft_payload_json);

        // Should fallback to generated_name
        $this->assertEquals('GENERATED_FALLBACK_NAME', $metaPayload['name']);
    }

    /**
     * Fix #3: Objective mapping
     * Test that objectives are correctly mapped to Meta format
     */
    public function test_objective_mapping_to_meta_format()
    {
        $service = app(MetaCampaignWriteService::class);

        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('mapObjectiveToMeta');
        $method->setAccessible(true);

        // Test lowercase mapping
        $this->assertEquals('OUTCOME_LEADS', $method->invoke($service, 'leads'));
        $this->assertEquals('OUTCOME_TRAFFIC', $method->invoke($service, 'traffic'));
        $this->assertEquals('OUTCOME_AWARENESS', $method->invoke($service, 'awareness'));

        // Test uppercase mapping
        $this->assertEquals('OUTCOME_LEADS', $method->invoke($service, 'LEADS'));
        $this->assertEquals('OUTCOME_TRAFFIC', $method->invoke($service, 'TRAFFIC'));

        // Test already in Meta format
        $this->assertEquals('OUTCOME_ENGAGEMENT', $method->invoke($service, 'OUTCOME_ENGAGEMENT'));

        // Test unknown objective (should fallback)
        $this->assertEquals('OUTCOME_TRAFFIC', $method->invoke($service, 'unknown_objective'));
    }

    /**
     * Fix #3: Budget from multiple sources
     * Test that budget uses proper fallback chain
     */
    public function test_budget_fallback_chain()
    {
        $briefing = CampaignBriefing::factory()->create([
            'budget_amount' => 100.00,
        ]);

        $template = CampaignTemplate::factory()->create([
            'default_budget' => 75.00,
        ]);

        // Test 1: campaign.daily_budget takes priority
        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test',
                    'daily_budget' => 50.00,
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('translateDraftToMetaFormat');
        $method->setAccessible(true);

        $metaPayload = $method->invoke($service, $draft, $draft->draft_payload_json);
        $this->assertEquals(5000, $metaPayload['daily_budget']); // 50.00 * 100

        // Test 2: falls back to briefing budget
        $draft2 = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test',
                ],
            ],
        ]);

        $metaPayload2 = $method->invoke($service, $draft2, $draft2->draft_payload_json);
        $this->assertEquals(10000, $metaPayload2['daily_budget']); // 100.00 * 100 from briefing
    }

    /**
     * Fix #4: Non-retryable exception detection
     * Test that non-retryable errors are correctly identified
     */
    public function test_non_retryable_exception_detection()
    {
        // Test NonRetryablePublishException itself
        $nonRetryable = new NonRetryablePublishException('No valid Meta ad account ID');
        $this->assertTrue(NonRetryablePublishException::isNonRetryable($nonRetryable));

        // Test exception with non-retryable message pattern
        $invalidAccount = new \Exception('No valid Meta ad account ID resolved for draft');
        $this->assertTrue(NonRetryablePublishException::isNonRetryable($invalidAccount));

        $objectNotExist = new \Exception("Object with ID 'act_' does not exist");
        $this->assertTrue(NonRetryablePublishException::isNonRetryable($objectNotExist));

        // Test retryable exception
        $networkError = new \Exception('Connection timeout');
        $this->assertFalse(NonRetryablePublishException::isNonRetryable($networkError));
    }

    /**
     * Integration test: Full payload mapping with real data
     */
    public function test_full_payload_mapping_integration()
    {
        $briefing = CampaignBriefing::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'LEADS',
            'budget_amount' => 50.00,
        ]);

        $template = CampaignTemplate::factory()->create([
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'LEADS',
            'funnel_stage' => 'PROSPECTING',
            'theme' => 'besparingscalculator',
        ]);

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'generated_name' => 'KIS_NL_PROSPECTING_LEADS_BESPARINGSCALCULATOR_202603',
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'KIS_NL_PROSPECTING_LEADS_BESPARINGSCALCULATOR_202603',
                    'objective' => 'LEADS',
                    'daily_budget' => 50.00,
                    'status' => 'PAUSED',
                ],
                'ad_sets' => [],
                'ads' => [],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('translateDraftToMetaFormat');
        $method->setAccessible(true);

        $metaPayload = $method->invoke($service, $draft, $draft->draft_payload_json);

        // Assert all fields are correctly mapped
        $this->assertEquals('KIS_NL_PROSPECTING_LEADS_BESPARINGSCALCULATOR_202603', $metaPayload['name']);
        $this->assertEquals('OUTCOME_LEADS', $metaPayload['objective']);
        $this->assertEquals(5000, $metaPayload['daily_budget']);
        $this->assertEquals('PAUSED', $metaPayload['status']);
        $this->assertEquals('AUCTION', $metaPayload['buying_type']);
    }

    /**
     * Fix: Internal status to Meta status mapping
     * Test that internal draft statuses are mapped to valid Meta statuses
     */
    public function test_internal_status_mapped_to_valid_meta_status()
    {
        $service = app(MetaCampaignWriteService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('mapInternalStatusToMeta');
        $method->setAccessible(true);

        // Test internal statuses map to PAUSED
        $this->assertEquals('PAUSED', $method->invoke($service, 'draft'));
        $this->assertEquals('PAUSED', $method->invoke($service, 'ready_for_review'));
        $this->assertEquals('PAUSED', $method->invoke($service, 'approved'));
        $this->assertEquals('PAUSED', $method->invoke($service, 'publishing'));
        $this->assertEquals('PAUSED', $method->invoke($service, 'published'));

        // Test valid Meta statuses pass through
        $this->assertEquals('ACTIVE', $method->invoke($service, 'ACTIVE'));
        $this->assertEquals('PAUSED', $method->invoke($service, 'PAUSED'));
        $this->assertEquals('DELETED', $method->invoke($service, 'DELETED'));
        $this->assertEquals('ARCHIVED', $method->invoke($service, 'ARCHIVED'));

        // Test lowercase valid Meta statuses
        $this->assertEquals('ACTIVE', $method->invoke($service, 'active'));
        $this->assertEquals('PAUSED', $method->invoke($service, 'paused'));

        // Test unknown status defaults to PAUSED
        $this->assertEquals('PAUSED', $method->invoke($service, 'unknown_status'));
    }

    /**
     * Fix: Draft status in payload is mapped correctly
     * Test that when draft payload contains status="draft", it's mapped to PAUSED
     */
    public function test_draft_status_in_payload_mapped_to_paused()
    {
        $draft = CampaignDraft::factory()->create([
            'status' => 'draft',
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'Test Campaign',
                    'objective' => 'LEADS',
                    'status' => 'draft',  // Invalid Meta status
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('translateDraftToMetaFormat');
        $method->setAccessible(true);

        $metaPayload = $method->invoke($service, $draft, $draft->draft_payload_json);

        // Assert status is mapped to PAUSED, not 'draft'
        $this->assertEquals('PAUSED', $metaPayload['status']);
        $this->assertNotEquals('draft', $metaPayload['status']);
    }

    /**
     * Fix: Meta 400 validation errors are non-retryable
     * Test that handleErrorResponse correctly classifies 400 errors
     */
    public function test_meta_400_validation_error_is_non_retryable()
    {
        // Test handleErrorResponse directly since Http::fake has limitations
        $client = new MetaWriteClient();

        // Create a mock response with 400 status
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('status')->willReturn(400);
        $mockResponse->method('json')->willReturn([
            'error' => [
                'message' => '(#100) status must be one of the following values: ACTIVE, PAUSED, DELETED, ARCHIVED',
                'code' => 100,
                'type' => 'OAuthException',
            ],
        ]);

        $this->expectException(NonRetryablePublishException::class);
        $this->expectExceptionMessage('Meta API validation error (400)');

        // Use reflection to test protected method directly
        $reflection = new \ReflectionClass($client);
        $method = $reflection->getMethod('handleErrorResponse');
        $method->setAccessible(true);
        $method->invoke($client, $mockResponse);
    }

    /**
     * Fix: Meta 400 validation error pattern detection
     * Test that specific error messages are detected as non-retryable
     */
    public function test_meta_400_error_patterns_detected()
    {
        // Test status validation error
        $statusError = new \Exception('Meta API validation error (400): status must be one of the following values: ACTIVE, PAUSED');
        $this->assertTrue(NonRetryablePublishException::isNonRetryable($statusError));

        // Test Meta error code #100
        $errorCode100 = new \Exception('Meta API Error: (#100) Invalid parameter');
        $this->assertTrue(NonRetryablePublishException::isNonRetryable($errorCode100));

        // Test retryable errors (5xx, network)
        $serverError = new \Exception('Meta API Error (500): Internal server error');
        $this->assertFalse(NonRetryablePublishException::isNonRetryable($serverError));

        $networkError = new \Exception('Connection timeout');
        $this->assertFalse(NonRetryablePublishException::isNonRetryable($networkError));
    }

    /**
     * Fix: Meta 500 errors remain retryable
     * Test that 5xx errors do NOT throw NonRetryablePublishException
     */
    public function test_meta_500_error_is_retryable()
    {
        $client = new MetaWriteClient();

        // Create a mock response with 500 status
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('status')->willReturn(500);
        $mockResponse->method('json')->willReturn([
            'error' => [
                'message' => 'Internal server error',
                'code' => 1,
                'type' => 'OAuthException',
            ],
        ]);

        // Should throw regular Exception, not NonRetryablePublishException
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Meta API Error (500)');

        try {
            // Use reflection to test protected method
            $reflection = new \ReflectionClass($client);
            $method = $reflection->getMethod('handleErrorResponse');
            $method->setAccessible(true);
            $method->invoke($client, $mockResponse);
        } catch (\Exception $e) {
            // Verify it's NOT a NonRetryablePublishException
            $this->assertNotInstanceOf(NonRetryablePublishException::class, $e);
            throw $e;
        }
    }

    /**
     * Integration test: Full payload with status mapping
     * Test complete flow from draft with internal status to Meta payload
     */
    public function test_full_payload_with_internal_status_mapping()
    {
        $user = User::factory()->create();
        $briefing = CampaignBriefing::factory()->create([
            'created_by' => $user->id,
            'brand' => 'KIS',
            'objective' => 'LEADS',
            'budget_amount' => 50.00,
        ]);

        $draft = CampaignDraft::factory()->create([
            'briefing_id' => $briefing->id,
            'status' => 'approved',  // Internal status
            'generated_name' => 'TEST_CAMPAIGN_202603',
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'TEST_CAMPAIGN_202603',
                    'objective' => 'LEADS',
                    'daily_budget' => 50.00,
                    'status' => 'ready_for_review',  // Another internal status
                ],
            ],
        ]);

        $service = app(MetaCampaignWriteService::class);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('translateDraftToMetaFormat');
        $method->setAccessible(true);

        $metaPayload = $method->invoke($service, $draft, $draft->draft_payload_json);

        // Assert internal status is mapped to valid Meta status
        $this->assertEquals('PAUSED', $metaPayload['status']);
        $this->assertContains($metaPayload['status'], ['ACTIVE', 'PAUSED', 'DELETED', 'ARCHIVED']);

        // Assert other fields remain correct
        $this->assertEquals('TEST_CAMPAIGN_202603', $metaPayload['name']);
        $this->assertEquals('OUTCOME_LEADS', $metaPayload['objective']);
    }
}
