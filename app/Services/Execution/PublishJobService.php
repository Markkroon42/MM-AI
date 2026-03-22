<?php

namespace App\Services\Execution;

use App\Enums\PublishActionTypeEnum;
use App\Enums\PublishJobStatusEnum;
use App\Jobs\Execution\ExecutePublishJob;
use App\Models\AuditLog;
use App\Models\CampaignDraft;
use App\Models\PublishJob;
use App\Services\Meta\MetaCampaignWriteService;
use Illuminate\Support\Facades\Log;

class PublishJobService
{
    public function __construct(
        protected MetaCampaignWriteService $metaCampaignWriteService
    ) {}

    /**
     * Create a publish job
     */
    public function create(
        CampaignDraft $draft,
        PublishActionTypeEnum $actionType,
        array $payload,
        string $provider = 'meta'
    ): PublishJob {
        Log::info('[PUBLISH_JOB_SERVICE] Creating publish job', [
            'draft_id' => $draft->id,
            'action_type' => $actionType->value,
            'provider' => $provider,
        ]);

        $job = PublishJob::create([
            'draft_id' => $draft->id,
            'provider' => $provider,
            'action_type' => $actionType->value,
            'payload_json' => $payload,
            'status' => PublishJobStatusEnum::PENDING->value,
            'attempts' => 0,
        ]);

        AuditLog::log(
            'publish_job_created',
            $job,
            null,
            [
                'action_type' => $actionType->value,
                'draft_id' => $draft->id,
            ]
        );

        // Fix Issue #3: Automatically dispatch publish job execution
        Log::info('[PUBLISH_JOB_SERVICE] Dispatching publish job execution', [
            'job_id' => $job->id,
            'draft_id' => $draft->id,
        ]);

        ExecutePublishJob::dispatch($job);

        Log::info('[PUBLISH_JOB_SERVICE] Publish job dispatched to queue', [
            'job_id' => $job->id,
        ]);

        return $job;
    }

    /**
     * Run a publish job
     * Fix Issue #3: Enhanced logging for execution tracking
     * Extended: Duplicate execution protection
     */
    public function run(PublishJob $job): PublishJob
    {
        Log::info('[PUBLISH_JOB_SERVICE] Running publish job', [
            'job_id' => $job->id,
            'action_type' => $job->action_type,
            'attempts' => $job->attempts,
        ]);

        // Duplicate execution protection: Check if job already succeeded
        if ($job->status === PublishJobStatusEnum::SUCCESS->value) {
            Log::warning('[PUBLISH_JOB_EXECUTION] Job already completed successfully, skipping execution', [
                'job_id' => $job->id,
                'executed_at' => $job->executed_at,
            ]);
            return $job;
        }

        $this->markRunning($job);

        try {
            $response = null;

            // Execute based on action type
            switch ($job->action_type) {
                case PublishActionTypeEnum::PUBLISH_CAMPAIGN_DRAFT->value:
                    if ($job->draft) {
                        Log::info('[PUBLISH_JOB_EXECUTION] Starting Meta write call', [
                            'job_id' => $job->id,
                            'draft_id' => $job->draft->id,
                        ]);
                        $response = $this->metaCampaignWriteService->publishDraft($job->draft);
                        Log::info('[PUBLISH_JOB_EXECUTION] Meta write call completed', [
                            'job_id' => $job->id,
                        ]);
                    }
                    break;

                case PublishActionTypeEnum::PAUSE_CAMPAIGN->value:
                    // Handle pause campaign - would need campaign model from payload
                    break;

                case PublishActionTypeEnum::UPDATE_CAMPAIGN_BUDGET->value:
                    // Handle budget update - would need campaign model from payload
                    break;

                default:
                    throw new \Exception("Unknown action type: {$job->action_type}");
            }

            $this->markSuccess($job, $response ?? []);

            Log::info('[PUBLISH_JOB_EXECUTION] Success', [
                'job_id' => $job->id,
            ]);

            return $job->fresh();
        } catch (\Exception $e) {
            $this->markFailed($job, $e->getMessage());

            Log::error('[PUBLISH_JOB_EXECUTION] Failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            Log::error('[PUBLISH_JOB_SERVICE] Publish job failed', [
                'job_id' => $job->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mark job as running
     */
    public function markRunning(PublishJob $job): void
    {
        $job->update([
            'status' => PublishJobStatusEnum::RUNNING->value,
            'attempts' => $job->attempts + 1,
        ]);

        Log::info('[PUBLISH_JOB_SERVICE] Publish job marked as running', [
            'job_id' => $job->id,
            'attempts' => $job->attempts,
        ]);
    }

    /**
     * Mark job as successful
     */
    public function markSuccess(PublishJob $job, array $response): void
    {
        $job->update([
            'status' => PublishJobStatusEnum::SUCCESS->value,
            'response_json' => $response,
            'executed_at' => now(),
            'error_message' => null,
        ]);

        AuditLog::log(
            'publish_job_executed_success',
            $job,
            ['status' => PublishJobStatusEnum::RUNNING->value],
            ['status' => PublishJobStatusEnum::SUCCESS->value],
            ['response' => $response]
        );

        Log::info('[PUBLISH_JOB_SERVICE] Publish job completed successfully', [
            'job_id' => $job->id,
        ]);
    }

    /**
     * Mark job as failed
     */
    public function markFailed(PublishJob $job, string $error): void
    {
        $job->update([
            'status' => PublishJobStatusEnum::FAILED->value,
            'error_message' => $error,
            'executed_at' => now(),
        ]);

        AuditLog::log(
            'publish_job_executed_failed',
            $job,
            ['status' => PublishJobStatusEnum::RUNNING->value],
            ['status' => PublishJobStatusEnum::FAILED->value],
            [
                'error' => $error,
                'attempts' => $job->attempts,
            ]
        );

        Log::error('[PUBLISH_JOB_SERVICE] Publish job marked as failed', [
            'job_id' => $job->id,
            'error' => $error,
            'attempts' => $job->attempts,
        ]);
    }
}
