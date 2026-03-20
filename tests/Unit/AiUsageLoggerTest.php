<?php

namespace Tests\Unit;

use App\Models\AiPromptConfig;
use App\Models\AiUsageLog;
use App\Models\CampaignBriefing;
use App\Services\AI\AiUsageLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected AiUsageLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new AiUsageLogger();
    }

    public function test_starts_log_with_running_status(): void
    {
        $config = AiPromptConfig::factory()->create();
        $briefing = CampaignBriefing::factory()->create();

        $log = $this->logger->start(
            'TestAgent',
            $config,
            $briefing,
            null,
            ['test' => 'data']
        );

        $this->assertInstanceOf(AiUsageLog::class, $log);
        $this->assertEquals('RUNNING', $log->status);
        $this->assertEquals('TestAgent', $log->agent_name);
        $this->assertEquals($config->id, $log->prompt_config_id);
        $this->assertNotNull($log->started_at);
    }

    public function test_marks_success_correctly(): void
    {
        $config = AiPromptConfig::factory()->create();
        $log = AiUsageLog::factory()->create([
            'prompt_config_id' => $config->id,
            'status' => 'RUNNING',
        ]);

        $this->logger->markSuccess(
            $log,
            ['result' => 'data'],
            100,
            200,
            0.05
        );

        $log->refresh();

        $this->assertEquals('SUCCESS', $log->status);
        $this->assertEquals(100, $log->tokens_input);
        $this->assertEquals(200, $log->tokens_output);
        $this->assertEquals(0.05, (float)$log->cost_estimate);
        $this->assertNotNull($log->finished_at);
    }

    public function test_marks_failed_correctly(): void
    {
        $config = AiPromptConfig::factory()->create();
        $log = AiUsageLog::factory()->create([
            'prompt_config_id' => $config->id,
            'status' => 'RUNNING',
        ]);

        $this->logger->markFailed($log, 'Test error message');

        $log->refresh();

        $this->assertEquals('FAILED', $log->status);
        $this->assertEquals('Test error message', $log->error_message);
        $this->assertNotNull($log->finished_at);
    }

    public function test_calculates_cost(): void
    {
        $cost = $this->logger->calculateCost('gpt-4-turbo-preview', 1000, 2000);

        // Expected: (1000/1000 * 0.01) + (2000/1000 * 0.03) = 0.01 + 0.06 = 0.07
        $this->assertEquals(0.07, $cost);
    }
}
