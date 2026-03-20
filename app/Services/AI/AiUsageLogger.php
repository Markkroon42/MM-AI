<?php

namespace App\Services\AI;

use App\Models\AiPromptConfig;
use App\Models\AiUsageLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AiUsageLogger
{
    /**
     * Start a new AI usage log
     *
     * @param string $agentName
     * @param AiPromptConfig $config
     * @param Model|null $source
     * @param Model|null $target
     * @param array $inputPayload
     * @return AiUsageLog
     */
    public function start(
        string $agentName,
        AiPromptConfig $config,
        ?Model $source,
        ?Model $target,
        array $inputPayload
    ): AiUsageLog {
        Log::info('[AI_USAGE_LOGGER] Starting log', [
            'agent_name' => $agentName,
            'config_key' => $config->key,
        ]);

        $log = AiUsageLog::create([
            'prompt_config_id' => $config->id,
            'agent_name' => $agentName,
            'source_type' => $source ? get_class($source) : null,
            'source_id' => $source?->id,
            'target_type' => $target ? get_class($target) : null,
            'target_id' => $target?->id,
            'model' => $config->model,
            'input_payload_json' => $inputPayload,
            'status' => 'RUNNING',
            'started_at' => now(),
        ]);

        return $log;
    }

    /**
     * Mark log as successful
     *
     * @param AiUsageLog $log
     * @param array $outputPayload
     * @param int $tokensIn
     * @param int $tokensOut
     * @param float $cost
     * @return void
     */
    public function markSuccess(
        AiUsageLog $log,
        array $outputPayload,
        int $tokensIn,
        int $tokensOut,
        float $cost
    ): void {
        Log::info('[AI_USAGE_LOGGER] Marking success', [
            'log_id' => $log->id,
            'tokens_input' => $tokensIn,
            'tokens_output' => $tokensOut,
            'cost' => $cost,
        ]);

        $log->update([
            'output_payload_json' => $outputPayload,
            'status' => 'SUCCESS',
            'tokens_input' => $tokensIn,
            'tokens_output' => $tokensOut,
            'cost_estimate' => $cost,
            'finished_at' => now(),
        ]);
    }

    /**
     * Mark log as failed
     *
     * @param AiUsageLog $log
     * @param string $error
     * @return void
     */
    public function markFailed(AiUsageLog $log, string $error): void
    {
        Log::error('[AI_USAGE_LOGGER] Marking failed', [
            'log_id' => $log->id,
            'error' => $error,
        ]);

        $log->update([
            'status' => 'FAILED',
            'error_message' => $error,
            'finished_at' => now(),
        ]);
    }

    /**
     * Calculate cost estimate
     *
     * @param string $model
     * @param int $tokensIn
     * @param int $tokensOut
     * @return float
     */
    public function calculateCost(string $model, int $tokensIn, int $tokensOut): float
    {
        // Pricing per 1K tokens (as of 2024)
        $pricing = [
            'gpt-4-turbo-preview' => ['input' => 0.01, 'output' => 0.03],
            'gpt-4' => ['input' => 0.03, 'output' => 0.06],
            'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],
            'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
        ];

        $rates = $pricing[$model] ?? ['input' => 0.01, 'output' => 0.03];

        $inputCost = ($tokensIn / 1000) * $rates['input'];
        $outputCost = ($tokensOut / 1000) * $rates['output'];

        return $inputCost + $outputCost;
    }
}
