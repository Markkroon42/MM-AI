<?php

namespace App\Services\CampaignBuilder;

use App\Models\CampaignDraft;

class CampaignReadinessService
{
    public function calculateReadiness(CampaignDraft $draft): array
    {
        $checks = $this->performChecks($draft);

        $totalChecks = count($checks);
        $passedChecks = count(array_filter($checks, fn($check) => $check['passed']));

        $percentage = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100) : 0;

        return [
            'percentage' => $percentage,
            'level' => $this->getReadinessLevel($percentage),
            'checks' => $checks,
            'passed_count' => $passedChecks,
            'total_count' => $totalChecks,
        ];
    }

    protected function performChecks(CampaignDraft $draft): array
    {
        $payload = $draft->draft_payload_json ?? [];

        return [
            [
                'name' => 'Briefing complete',
                'passed' => $draft->briefing_id !== null && $draft->briefing !== null,
                'category' => 'briefing',
            ],
            [
                'name' => 'Template selected',
                'passed' => $draft->template_id !== null,
                'category' => 'template',
            ],
            [
                'name' => 'Campaign name',
                // Fix #6: Check payload['campaign']['name'] to align with validation
                'passed' => !empty($payload['campaign']['name'] ?? null) || !empty($draft->generated_name),
                'category' => 'campaign',
            ],
            [
                'name' => 'Campaign objective',
                'passed' => !empty($payload['campaign']['objective'] ?? null),
                'category' => 'campaign',
            ],
            [
                'name' => 'Budget defined',
                'passed' => !empty($payload['campaign']['daily_budget'] ?? null) ||
                           !empty($payload['campaign']['lifetime_budget'] ?? null) ||
                           ($draft->briefing && $draft->briefing->budget_amount > 0),
                'category' => 'budget',
            ],
            [
                'name' => 'At least 1 ad set',
                'passed' => !empty($payload['ad_sets']) && count($payload['ad_sets']) > 0,
                'category' => 'structure',
            ],
            [
                'name' => 'At least 1 ad',
                'passed' => !empty($payload['ads']) && count($payload['ads']) > 0,
                'category' => 'structure',
            ],
            [
                'name' => 'Landing page URL',
                'passed' => !empty($draft->briefing?->landing_page_url),
                'category' => 'tracking',
            ],
            [
                'name' => 'Copy available',
                'passed' => $this->hasCopy($payload),
                'category' => 'content',
            ],
            [
                'name' => 'Creative concepts available',
                'passed' => $draft->draftEnrichments()->where('enrichment_type', 'creative_suggestions')->exists(),
                'category' => 'content',
            ],
        ];
    }

    protected function hasCopy(array $payload): bool
    {
        $ads = $payload['ads'] ?? [];

        foreach ($ads as $ad) {
            if (!empty($ad['creative']['object_story_spec']['link_data']['message'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    protected function getReadinessLevel(int $percentage): string
    {
        return match(true) {
            $percentage >= 90 => 'ready',
            $percentage >= 70 => 'almost-ready',
            $percentage >= 40 => 'in-progress',
            default => 'incomplete',
        };
    }
}
