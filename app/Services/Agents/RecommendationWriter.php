<?php

namespace App\Services\Agents;

use App\Models\CampaignRecommendation;
use Illuminate\Support\Facades\Log;

class RecommendationWriter
{
    /**
     * Write a recommendation to the database with duplicate prevention.
     *
     * @param array $recommendationData
     * @return CampaignRecommendation|null
     */
    public function write(array $recommendationData): ?CampaignRecommendation
    {
        try {
            // Generate a hash for duplicate detection
            $hash = $this->generateHash($recommendationData);

            // Check if an open recommendation with the same hash already exists
            $existing = CampaignRecommendation::where('recommendation_type', $recommendationData['recommendation_type'])
                ->where('source_agent', $recommendationData['source_agent'])
                ->where('meta_campaign_id', $recommendationData['meta_campaign_id'] ?? null)
                ->where('meta_ad_set_id', $recommendationData['meta_ad_set_id'] ?? null)
                ->where('meta_ad_id', $recommendationData['meta_ad_id'] ?? null)
                ->whereIn('status', ['new', 'reviewing'])
                ->first();

            if ($existing) {
                Log::info('[RECOMMENDATION_WRITER] Duplicate recommendation detected, skipping', [
                    'type' => $recommendationData['recommendation_type'],
                    'source' => $recommendationData['source_agent'],
                    'existing_id' => $existing->id,
                    'hash' => $hash,
                ]);

                return null;
            }

            // Create the recommendation
            $recommendation = CampaignRecommendation::create($recommendationData);

            Log::info('[RECOMMENDATION_WRITER] Recommendation created successfully', [
                'id' => $recommendation->id,
                'type' => $recommendation->recommendation_type,
                'severity' => $recommendation->severity,
                'source' => $recommendation->source_agent,
                'campaign_id' => $recommendation->meta_campaign_id,
            ]);

            return $recommendation;

        } catch (\Exception $e) {
            Log::error('[RECOMMENDATION_WRITER] Failed to write recommendation', [
                'error' => $e->getMessage(),
                'data' => $recommendationData,
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Write multiple recommendations at once.
     *
     * @param array $recommendations
     * @return array
     */
    public function writeMany(array $recommendations): array
    {
        $created = [];

        foreach ($recommendations as $recommendationData) {
            $recommendation = $this->write($recommendationData);

            if ($recommendation) {
                $created[] = $recommendation;
            }
        }

        Log::info('[RECOMMENDATION_WRITER] Batch write completed', [
            'total_attempted' => count($recommendations),
            'total_created' => count($created),
        ]);

        return $created;
    }

    /**
     * Generate a hash for duplicate detection.
     *
     * @param array $data
     * @return string
     */
    private function generateHash(array $data): string
    {
        $hashParts = [
            $data['recommendation_type'] ?? '',
            $data['source_agent'] ?? '',
            $data['meta_campaign_id'] ?? 'null',
            $data['meta_ad_set_id'] ?? 'null',
            $data['meta_ad_id'] ?? 'null',
        ];

        return md5(implode('|', $hashParts));
    }
}
