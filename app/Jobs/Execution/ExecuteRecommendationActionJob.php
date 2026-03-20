<?php

namespace App\Jobs\Execution;

use App\Models\CampaignRecommendation;
use App\Services\Execution\RecommendationExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecuteRecommendationActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CampaignRecommendation $recommendation
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RecommendationExecutionService $executionService): void
    {
        Log::info('[EXECUTE_RECOMMENDATION_JOB] Starting recommendation execution', [
            'recommendation_id' => $this->recommendation->id,
            'recommendation_type' => $this->recommendation->recommendation_type,
        ]);

        try {
            $executionService->execute($this->recommendation);

            Log::info('[EXECUTE_RECOMMENDATION_JOB] Recommendation executed successfully', [
                'recommendation_id' => $this->recommendation->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[EXECUTE_RECOMMENDATION_JOB] Recommendation execution failed', [
                'recommendation_id' => $this->recommendation->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to allow queue retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[EXECUTE_RECOMMENDATION_JOB] Recommendation execution failed permanently', [
            'recommendation_id' => $this->recommendation->id,
            'error' => $exception->getMessage(),
        ]);

        // Update recommendation status if needed
        $this->recommendation->update([
            'status' => 'failed',
        ]);
    }
}
