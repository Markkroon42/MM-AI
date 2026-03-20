<?php

namespace App\Services\Meta;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaApiClient
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
     * Make a GET request to the Meta Graph API
     */
    public function get(string $endpoint, array $params = []): array
    {
        $url = $this->buildUrl($endpoint);
        
        $params['access_token'] = $this->accessToken;

        Log::info('[META_API_CALL] GET Request', [
            'url' => $url,
            'params' => array_diff_key($params, ['access_token' => '']),
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, $this->retryDelay)
                ->get($url, $params);

            $this->logResponse($response);

            if ($response->failed()) {
                $this->handleErrorResponse($response);
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('[META_API_CALL] Request failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get ad accounts for the business
     */
    public function getAdAccounts(?string $businessId = null): array
    {
        $businessId = $businessId ?? config('meta.business_id');

        if (!$businessId) {
            throw new \InvalidArgumentException('Business ID is required');
        }

        $endpoint = "{$businessId}/owned_ad_accounts";
        $fields = config('meta.default_fields.ad_accounts');

        Log::info('[META_API_CALL] Fetching ad accounts', [
            'business_id' => $businessId,
        ]);

        return $this->get($endpoint, ['fields' => $fields]);
    }

    /**
     * Get campaigns for an ad account
     */
    public function getCampaigns(string $adAccountId, array $filters = []): array
    {
        $endpoint = "{$adAccountId}/campaigns";
        $fields = config('meta.default_fields.campaigns');

        $params = array_merge(['fields' => $fields], $filters);

        Log::info('[META_API_CALL] Fetching campaigns', [
            'ad_account_id' => $adAccountId,
            'filters' => $filters,
        ]);

        return $this->get($endpoint, $params);
    }

    /**
     * Get ad sets for a campaign
     */
    public function getAdSets(string $campaignId, array $filters = []): array
    {
        $endpoint = "{$campaignId}/adsets";
        $fields = config('meta.default_fields.ad_sets');

        $params = array_merge(['fields' => $fields], $filters);

        Log::info('[META_API_CALL] Fetching ad sets', [
            'campaign_id' => $campaignId,
            'filters' => $filters,
        ]);

        return $this->get($endpoint, $params);
    }

    /**
     * Get ads for an ad set
     */
    public function getAds(string $adSetId, array $filters = []): array
    {
        $endpoint = "{$adSetId}/ads";
        $fields = config('meta.default_fields.ads');

        $params = array_merge(['fields' => $fields], $filters);

        Log::info('[META_API_CALL] Fetching ads', [
            'ad_set_id' => $adSetId,
            'filters' => $filters,
        ]);

        return $this->get($endpoint, $params);
    }

    /**
     * Get campaign insights
     */
    public function getCampaignInsights(
        string $campaignId,
        string $dateFrom,
        string $dateTo,
        string $level = 'campaign'
    ): array {
        $endpoint = "{$campaignId}/insights";
        $fields = config('meta.default_fields.insights');

        $params = [
            'fields' => $fields,
            'time_range' => json_encode([
                'since' => $dateFrom,
                'until' => $dateTo,
            ]),
            'level' => $level,
            'time_increment' => 1, // daily
        ];

        Log::info('[META_API_CALL] Fetching campaign insights', [
            'campaign_id' => $campaignId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'level' => $level,
        ]);

        return $this->get($endpoint, $params);
    }

    /**
     * Get ad account insights
     */
    public function getAdAccountInsights(
        string $adAccountId,
        string $dateFrom,
        string $dateTo
    ): array {
        $endpoint = "{$adAccountId}/insights";
        $fields = config('meta.default_fields.insights');

        $params = [
            'fields' => $fields,
            'time_range' => json_encode([
                'since' => $dateFrom,
                'until' => $dateTo,
            ]),
            'level' => 'account',
            'time_increment' => 1, // daily
        ];

        Log::info('[META_API_CALL] Fetching ad account insights', [
            'ad_account_id' => $adAccountId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        return $this->get($endpoint, $params);
    }

    /**
     * Build full API URL
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
        Log::info('[META_API_CALL] Response received', [
            'status' => $response->status(),
            'has_data' => $response->successful(),
        ]);
    }

    /**
     * Handle error response
     */
    protected function handleErrorResponse(Response $response): void
    {
        $error = $response->json();
        
        Log::error('[META_API_CALL] API Error', [
            'status' => $response->status(),
            'error' => $error,
        ]);

        $message = $error['error']['message'] ?? 'Unknown API error';
        $code = $error['error']['code'] ?? $response->status();

        throw new \Exception("Meta API Error ({$code}): {$message}");
    }
}
