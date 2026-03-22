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
     * Fix: Idempotency guard before execution
     */
    public function handle(PublishJobService $publishJobService): void
    {
        Log::info('[EXECUTE_PUBLISH_JOB] Starting publish job execution', [
            'job_id' => $this->publishJob->id,
            'action_type' => $this->publishJob->action_type,
            'attempt' => $this->attempts(),
        ]);

        // Fix: Idempotency guard - check job status before execution
        // Reload fresh data from database to avoid stale data issues
        $this->publishJob->refresh();

        if ($this->publishJob->status === 'success') {
            Log::warning('[EXECUTE_PUBLISH_JOB] Skipping because publish job already completed', [
                'job_id' => $this->publishJob->id,
                'executed_at' => $this->publishJob->executed_at,
            ]);
            $this->delete();
            return;
        }

        if ($this->publishJob->status === 'failed') {
            Log::warning('[EXECUTE_PUBLISH_JOB] Skipping because publish job already permanently failed', [
                'job_id' => $this->publishJob->id,
                'error' => $this->publishJob->error_message,
            ]);
            $this->delete();
            return;
        }

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

            Log::warning('[EXECUTE_PUBLISH_JOB] Stopping execution without retry', [
                'job_id' => $this->publishJob->id,
            ]);

            // Mark publish job as permanently failed
            try {
                app(PublishJobService::class)->markFailed($this->publishJob, $e->getMessage());
            } catch (\Exception $markException) {
                Log::error('[EXECUTE_PUBLISH_JOB] Failed to mark job as failed', [
                    'job_id' => $this->publishJob->id,
                    'error' => $markException->getMessage(),
                ]);
            }

            Log::warning('[PUBLISH_JOB_SERVICE] Publish job permanently failed without requeue', [
                'job_id' => $this->publishJob->id,
                'error' => $e->getMessage(),
            ]);

            // Delete job from queue to prevent retries
            $this->delete();

            // Important: Return without throwing or calling failed() to prevent queue retry
            return;
        } catch (\Exception $e) {
            // Check if message indicates non-retryable error
            if (NonRetryablePublishException::isNonRetryable($e)) {
                Log::error('[EXECUTE_PUBLISH_JOB] Non-retryable publish failure detected (by pattern)', [
                    'job_id' => $this->publishJob->id,
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                ]);

                Log::warning('[EXECUTE_PUBLISH_JOB] Stopping execution without retry', [
                    'job_id' => $this->publishJob->id,
                ]);

                // Mark publish job as permanently failed
                try {
                    app(PublishJobService::class)->markFailed($this->publishJob, $e->getMessage());
                } catch (\Exception $markException) {
                    Log::error('[EXECUTE_PUBLISH_JOB] Failed to mark job as failed', [
                        'job_id' => $this->publishJob->id,
                        'error' => $markException->getMessage(),
                    ]);
                }

                Log::warning('[PUBLISH_JOB_SERVICE] Publish job permanently failed without requeue', [
                    'job_id' => $this->publishJob->id,
                    'error' => $e->getMessage(),
                ]);

                // Delete job from queue to prevent retries
                $this->delete();

                // Important: Return without throwing or calling failed() to prevent queue retry
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

