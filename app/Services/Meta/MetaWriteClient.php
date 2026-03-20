<?php

namespace App\Services\Meta;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaWriteClient
{
    protected string $baseUrl;
    protected string $apiVersion;
    protected string $accessToken;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retryDelay;

    public function __construct()
    {
        $this->baseUrl = config('meta.graph_base_url');
        $this->apiVersion = config('meta.api_version');
        $this->accessToken = config('meta.access_token');
        $this->timeout = config('meta.timeout', 30);
        $this->retryTimes = config('meta.retry_times', 3);
        $this->retryDelay = config('meta.retry_delay', 1000);
    }

    /**
     * Update campaign status via Meta API
     */
    public function updateCampaignStatus(string $metaCampaignId, string $status): array
    {
        $url = $this->buildUrl($metaCampaignId);

        Log::info('[META_WRITE_API] Updating campaign status', [
            'campaign_id' => $metaCampaignId,
            'status' => $status,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay, function ($exception, $request) {
                    // Retry on transient failures
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                })
                ->post($url, [
                    'access_token' => $this->accessToken,
                    'status' => $status,
                ]);

            $this->logResponse($response);

            if ($response->failed()) {
                $this->handleErrorResponse($response);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('[META_WRITE_API] Campaign status update failed', [
                'campaign_id' => $metaCampaignId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update campaign budget via Meta API
     */
    public function updateCampaignBudget(string $metaCampaignId, float $dailyBudget): array
    {
        $url = $this->buildUrl($metaCampaignId);

        // Convert to cents (Meta expects budget in cents)
        $budgetInCents = (int) ($dailyBudget * 100);

        Log::info('[META_WRITE_API] Updating campaign budget', [
            'campaign_id' => $metaCampaignId,
            'daily_budget' => $dailyBudget,
            'budget_in_cents' => $budgetInCents,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay, function ($exception, $request) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                })
                ->post($url, [
                    'access_token' => $this->accessToken,
                    'daily_budget' => $budgetInCents,
                ]);

            $this->logResponse($response);

            if ($response->failed()) {
                $this->handleErrorResponse($response);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('[META_WRITE_API] Campaign budget update failed', [
                'campaign_id' => $metaCampaignId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create new campaign via Meta API
     */
    public function createCampaign(string $accountId, array $payload): array
    {
        $url = $this->buildUrl("act_{$accountId}/campaigns");

        Log::info('[META_WRITE_API] Creating campaign', [
            'account_id' => $accountId,
            'payload' => $payload,
        ]);

        try {
            $payload['access_token'] = $this->accessToken;

            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay, function ($exception, $request) {
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                })
                ->post($url, $payload);

            $this->logResponse($response);

            if ($response->failed()) {
                $this->handleErrorResponse($response);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('[META_WRITE_API] Campaign creation failed', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Build full URL for API endpoint
     */
    protected function buildUrl(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');
        return "{$this->baseUrl}/{$this->apiVersion}/{$endpoint}";
    }

    /**
     * Log API response
     */
    protected function logResponse(Response $response): void
    {
        Log::info('[META_WRITE_API] Response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
    }

    /**
     * Handle error responses
     */
    protected function handleErrorResponse(Response $response): void
    {
        $error = $response->json();

        Log::error('[META_WRITE_API] API Error', [
            'status' => $response->status(),
            'error' => $error,
        ]);

        $message = $error['error']['message'] ?? 'Unknown Meta API error';
        throw new \Exception("Meta API Error: {$message}");
    }
}
