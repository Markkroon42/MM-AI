<?php

namespace App\Jobs\Execution;

use App\Exceptions\NonRetryablePublishException;
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
     * Fix: Handle non-retryable exceptions
     */
    public function handle(PublishJobService $publishJobService): void
    {
        Log::info('[EXECUTE_PUBLISH_JOB] Starting publish job execution', [
            'job_id' => $this->publishJob->id,
            'action_type' => $this->publishJob->action_type,
            'attempt' => $this->attempts(),
        ]);

        try {
            $publishJobService->run($this->publishJob);

            Log::info('[EXECUTE_PUBLISH_JOB] Publish job executed successfully', [
                'job_id' => $this->publishJob->id,
            ]);
        } catch (NonRetryablePublishException $e) {
            // Fix: Non-retryable exceptions should stop immediately
            Log::error('[EXECUTE_PUBLISH_JOB] Non-retryable publish failure detected', [
                'job_id' => $this->publishJob->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            Log::warning('[EXECUTE_PUBLISH_JOB] Stopping retries due to invalid publish context', [
                'job_id' => $this->publishJob->id,
            ]);

            // Delete job from queue to prevent retries
            $this->delete();

            // Call failed handler directly (without throwing exception)
            $this->failed($e);

            // Important: Return without throwing to prevent queue from retrying
            return;
        } catch (\Exception $e) {
            // Check if message indicates non-retryable error
            if (NonRetryablePublishException::isNonRetryable($e)) {
                Log::error('[EXECUTE_PUBLISH_JOB] Non-retryable publish failure detected (by pattern)', [
                    'job_id' => $this->publishJob->id,
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                ]);

                Log::warning('[EXECUTE_PUBLISH_JOB] Stopping retries due to invalid publish context', [
                    'job_id' => $this->publishJob->id,
                ]);

                // Delete job from queue to prevent retries
                $this->delete();

                // Call failed handler directly (without throwing exception)
                $this->failed($e);

                // Important: Return without throwing to prevent queue from retrying
                return;
            }

            Log::error('[EXECUTE_PUBLISH_JOB] Publish job execution failed (retryable)', [
                'job_id' => $this->publishJob->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
            ]);

            // Re-throw retryable errors to allow queue retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     * Fix #4: Enhanced logging for non-retryable failures
     */
    public function failed(\Throwable $exception): void
    {
        $isNonRetryable = NonRetryablePublishException::isNonRetryable($exception);

        Log::error('[EXECUTE_PUBLISH_JOB] Publish job failed permanently', [
            'job_id' => $this->publishJob->id,
            'error' => $exception->getMessage(),
            'is_non_retryable' => $isNonRetryable,
            'reason' => $isNonRetryable ? 'Invalid publish context / validation error' : 'Max retries exceeded',
        ]);

        if ($isNonRetryable) {
            Log::warning('[PUBLISH_JOB_SERVICE] Publish job failed permanently due to invalid publish context', [
                'job_id' => $this->publishJob->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

