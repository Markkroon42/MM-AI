<?php

namespace App\Services\CampaignDraft;

use App\Models\CampaignTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CampaignTemplateService
{
    /**
     * Get all active templates
     */
    public function getActiveTemplates(): Collection
    {
        return CampaignTemplate::where('is_active', true)
            ->with('utmTemplate')
            ->orderBy('brand')
            ->orderBy('market')
            ->get();
    }

    /**
     * Apply template structure to briefing data
     */
    public function applyTemplate(CampaignTemplate $template, array $briefingData): array
    {
        Log::info('[CAMPAIGN_TEMPLATE_SERVICE] Applying template', [
            'template_id' => $template->id,
            'template_name' => $template->name,
        ]);

        $structure = $template->structure_json;

        // Merge template structure with briefing data
        $appliedStructure = [
            'campaign' => array_merge(
                $structure['campaign'] ?? [],
                [
                    'objective' => $briefingData['objective'] ?? $template->objective,
                    'daily_budget' => $briefingData['budget_amount'] ?? $template->default_budget,
                ]
            ),
            'ad_sets' => $this->applyAdSetStructure(
                $structure['ad_sets'] ?? [],
                $briefingData
            ),
            'ads' => $this->applyAdStructure(
                $structure['ads'] ?? [],
                $briefingData
            ),
        ];

        // Apply creative rules
        if (!empty($template->creative_rules_json)) {
            $appliedStructure['creative_rules'] = $template->creative_rules_json;
        }

        return $appliedStructure;
    }

    /**
     * Apply ad set structure
     */
    protected function applyAdSetStructure(array $adSetsStructure, array $briefingData): array
    {
        $adSets = [];

        foreach ($adSetsStructure as $index => $adSetTemplate) {
            $adSets[] = array_merge($adSetTemplate, [
                'target_audience' => $briefingData['target_audience'] ?? $adSetTemplate['target_audience'] ?? null,
                'optimization_goal' => $adSetTemplate['optimization_goal'] ?? 'LINK_CLICKS',
                'billing_event' => $adSetTemplate['billing_event'] ?? 'IMPRESSIONS',
            ]);
        }

        return $adSets;
    }

    /**
     * Apply ad structure
     */
    protected function applyAdStructure(array $adsStructure, array $briefingData): array
    {
        $ads = [];

        foreach ($adsStructure as $index => $adTemplate) {
            $ads[] = array_merge($adTemplate, [
                'creative' => array_merge($adTemplate['creative'] ?? [], [
                    'link_url' => $briefingData['landing_page_url'] ?? null,
                    'call_to_action_type' => $adTemplate['creative']['call_to_action_type'] ?? 'LEARN_MORE',
                ]),
            ]);
        }

        return $ads;
    }
}
