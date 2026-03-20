<?php

namespace App\Services\CampaignBuilder;

use App\Models\CampaignDraft;

class DraftValidationService
{
    public function validate(CampaignDraft $draft): array
    {
        $payload = $draft->draft_payload_json ?? [];
        $warnings = [];
        $blockers = [];
        $infos = [];

        // Check briefing
        if (!$draft->briefing_id) {
            $warnings[] = [
                'message' => 'No briefing linked to this draft',
                'category' => 'briefing',
                'severity' => 'warning',
            ];
        }

        // Check template
        if (!$draft->template_id) {
            $infos[] = [
                'message' => 'No template used - manual configuration',
                'category' => 'template',
                'severity' => 'info',
            ];
        }

        // Check campaign structure
        // Fix #6: Check both payload and generated_name for backward compatibility
        if (empty($payload['campaign']['name'] ?? null) && empty($draft->generated_name)) {
            $blockers[] = [
                'message' => 'Campaign name is required',
                'category' => 'campaign',
                'severity' => 'blocker',
            ];
        }

        if (empty($payload['campaign']['objective'] ?? null)) {
            $blockers[] = [
                'message' => 'Campaign objective is required',
                'category' => 'campaign',
                'severity' => 'blocker',
            ];
        }

        // Check budget
        $hasDailyBudget = !empty($payload['campaign']['daily_budget'] ?? null);
        $hasLifetimeBudget = !empty($payload['campaign']['lifetime_budget'] ?? null);
        $hasBriefingBudget = $draft->briefing && $draft->briefing->budget_amount > 0;

        if (!$hasDailyBudget && !$hasLifetimeBudget && !$hasBriefingBudget) {
            $blockers[] = [
                'message' => 'Budget is required (daily or lifetime)',
                'category' => 'budget',
                'severity' => 'blocker',
            ];
        }

        // Check ad sets
        $adSets = $payload['ad_sets'] ?? [];
        if (empty($adSets)) {
            $blockers[] = [
                'message' => 'At least one ad set is required',
                'category' => 'structure',
                'severity' => 'blocker',
            ];
        } else {
            foreach ($adSets as $index => $adSet) {
                if (empty($adSet['name'] ?? null)) {
                    $warnings[] = [
                        'message' => "Ad set #{$index} is missing a name",
                        'category' => 'structure',
                        'severity' => 'warning',
                    ];
                }
            }
        }

        // Check ads
        $ads = $payload['ads'] ?? [];
        if (empty($ads)) {
            $blockers[] = [
                'message' => 'At least one ad is required',
                'category' => 'structure',
                'severity' => 'blocker',
            ];
        } else {
            foreach ($ads as $index => $ad) {
                if (empty($ad['name'] ?? null)) {
                    $warnings[] = [
                        'message' => "Ad #{$index} is missing a name",
                        'category' => 'structure',
                        'severity' => 'warning',
                    ];
                }

                if (empty($ad['creative']['object_story_spec']['link_data']['message'] ?? null)) {
                    $warnings[] = [
                        'message' => "Ad '{$ad['name']}' is missing primary text",
                        'category' => 'content',
                        'severity' => 'warning',
                    ];
                }
            }
        }

        // Check landing page
        if (!$draft->briefing || empty($draft->briefing->landing_page_url)) {
            $warnings[] = [
                'message' => 'No landing page URL defined in briefing',
                'category' => 'tracking',
                'severity' => 'warning',
            ];
        }

        // Check approval status for publish
        if ($draft->status !== 'approved' && $draft->status !== 'draft') {
            $infos[] = [
                'message' => "Draft status: " . ucwords(str_replace('_', ' ', $draft->status)),
                'category' => 'approval',
                'severity' => 'info',
            ];
        }

        return [
            'blockers' => $blockers,
            'warnings' => $warnings,
            'infos' => $infos,
            'has_blockers' => count($blockers) > 0,
            'can_publish' => count($blockers) === 0 && $draft->status === 'approved',
            'can_request_review' => count($blockers) === 0 && $draft->status === 'draft',
        ];
    }
}
