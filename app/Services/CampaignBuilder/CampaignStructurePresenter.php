<?php

namespace App\Services\CampaignBuilder;

use App\Models\CampaignDraft;

class CampaignStructurePresenter
{
    public function present(CampaignDraft $draft): array
    {
        $payload = $draft->draft_payload_json ?? [];

        return [
            'campaign' => $this->presentCampaign($payload, $draft),
            'ad_sets' => $this->presentAdSets($payload),
            'ads' => $this->presentAds($payload),
            'summary' => $this->buildSummary($payload),
        ];
    }

    protected function presentCampaign(array $payload, CampaignDraft $draft): array
    {
        $campaign = $payload['campaign'] ?? [];

        return [
            'name' => $campaign['name'] ?? $draft->generated_name ?? 'Untitled Campaign',
            'objective' => $campaign['objective'] ?? null,
            'status' => $campaign['status'] ?? 'PAUSED',
            'daily_budget' => $campaign['daily_budget'] ?? null,
            'lifetime_budget' => $campaign['lifetime_budget'] ?? null,
            'budget_optimization' => $campaign['budget_optimization'] ?? false,
            'special_ad_categories' => $campaign['special_ad_categories'] ?? [],
        ];
    }

    protected function presentAdSets(array $payload): array
    {
        $adSets = $payload['ad_sets'] ?? [];
        $presented = [];

        foreach ($adSets as $index => $adSet) {
            $presented[] = [
                'index' => $index,
                'name' => $adSet['name'] ?? "Ad Set #{$index}",
                'daily_budget' => $adSet['daily_budget'] ?? null,
                'optimization_goal' => $adSet['optimization_goal'] ?? null,
                'billing_event' => $adSet['billing_event'] ?? 'IMPRESSIONS',
                'bid_strategy' => $adSet['bid_strategy'] ?? null,
                'targeting' => $this->presentTargeting($adSet['targeting'] ?? []),
                'placements' => $adSet['placements'] ?? [],
                'has_targeting' => !empty($adSet['targeting']),
                'has_budget' => !empty($adSet['daily_budget']),
                'completeness' => $this->calculateAdSetCompleteness($adSet),
            ];
        }

        return $presented;
    }

    protected function presentAds(array $payload): array
    {
        $ads = $payload['ads'] ?? [];
        $presented = [];

        foreach ($ads as $index => $ad) {
            $creative = $ad['creative'] ?? [];
            $linkData = $creative['object_story_spec']['link_data'] ?? [];

            $presented[] = [
                'index' => $index,
                'name' => $ad['name'] ?? "Ad #{$index}",
                'status' => $ad['status'] ?? 'PAUSED',
                'ad_set_index' => $ad['ad_set_index'] ?? 0,
                'creative_name' => $creative['name'] ?? null,
                'primary_text' => $linkData['message'] ?? null,
                'headline' => $linkData['name'] ?? null,
                'description' => $linkData['description'] ?? null,
                'cta' => $linkData['call_to_action']['type'] ?? null,
                'link' => $linkData['link'] ?? null,
                'has_copy' => !empty($linkData['message']),
                'has_headline' => !empty($linkData['name']),
                'has_cta' => !empty($linkData['call_to_action']['type']),
                'completeness' => $this->calculateAdCompleteness($ad),
            ];
        }

        return $presented;
    }

    protected function presentTargeting(array $targeting): array
    {
        return [
            'age_min' => $targeting['age_min'] ?? null,
            'age_max' => $targeting['age_max'] ?? null,
            'genders' => $targeting['genders'] ?? [],
            'geo_locations' => $targeting['geo_locations'] ?? [],
            'interests' => $targeting['interests'] ?? [],
            'behaviors' => $targeting['behaviors'] ?? [],
            'summary' => $this->buildTargetingSummary($targeting),
        ];
    }

    protected function buildTargetingSummary(array $targeting): string
    {
        $parts = [];

        if (!empty($targeting['age_min']) || !empty($targeting['age_max'])) {
            $ageMin = $targeting['age_min'] ?? 18;
            $ageMax = $targeting['age_max'] ?? 65;
            $parts[] = "Age {$ageMin}-{$ageMax}";
        }

        if (!empty($targeting['genders'])) {
            $genders = $targeting['genders'];
            if (in_array(1, $genders) && in_array(2, $genders)) {
                $parts[] = "All genders";
            } elseif (in_array(1, $genders)) {
                $parts[] = "Men";
            } elseif (in_array(2, $genders)) {
                $parts[] = "Women";
            }
        }

        if (!empty($targeting['geo_locations']['countries'])) {
            $countries = $targeting['geo_locations']['countries'];
            $parts[] = implode(', ', array_slice($countries, 0, 3));
        }

        return !empty($parts) ? implode(' • ', $parts) : 'No targeting defined';
    }

    protected function calculateAdSetCompleteness(array $adSet): int
    {
        $checks = [
            !empty($adSet['name']),
            !empty($adSet['optimization_goal']),
            !empty($adSet['targeting']),
            !empty($adSet['daily_budget']) || !empty($adSet['lifetime_budget']),
        ];

        $passed = count(array_filter($checks));
        return round(($passed / count($checks)) * 100);
    }

    protected function calculateAdCompleteness(array $ad): int
    {
        $linkData = $ad['creative']['object_story_spec']['link_data'] ?? [];

        $checks = [
            !empty($ad['name']),
            !empty($linkData['message']),
            !empty($linkData['name']),
            !empty($linkData['call_to_action']['type']),
            !empty($linkData['link']),
        ];

        $passed = count(array_filter($checks));
        return round(($passed / count($checks)) * 100);
    }

    protected function buildSummary(array $payload): array
    {
        $adSets = $payload['ad_sets'] ?? [];
        $ads = $payload['ads'] ?? [];

        return [
            'ad_set_count' => count($adSets),
            'ad_count' => count($ads),
            'has_structure' => count($adSets) > 0 && count($ads) > 0,
        ];
    }
}
