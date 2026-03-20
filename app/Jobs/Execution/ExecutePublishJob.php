<?php

namespace App\Jobs\Execution;

use App\Models\PublishJob;
use App\Services\Execution\PublishJobService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExecutePublishJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PublishJob $publishJob
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PublishJobService $publishJobService): void
    {
        Log::info('[EXECUTE_PUBLISH_JOB] Starting publish job execution', [
            'job_id' => $this->publishJob->id,
            'action_type' => $this->publishJob->action_type,
        ]);

        try {
            $publishJobService->run($this->publishJob);

            Log::info('[EXECUTE_PUBLISH_JOB] Publish job executed successfully', [
                'job_id' => $this->publishJob->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[EXECUTE_PUBLISH_JOB] Publish job execution failed', [
                'job_id' => $this->publishJob->id,
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
        Log::error('[EXECUTE_PUBLISH_JOB] Publish job failed permanently', [
            'job_id' => $this->publishJob->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
