<?php

namespace App\Services\AI\PromptBuilders;

use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;

class CopyPromptBuilder
{
    /**
     * Build prompt context for campaign briefing
     *
     * @param CampaignBriefing $briefing
     * @return array
     */
    public function buildForBriefing(CampaignBriefing $briefing): array
    {
        return [
            'brand' => $briefing->brand ?? 'Not specified',
            'market' => $briefing->market ?? 'Not specified',
            'objective' => $briefing->objective ?? 'Not specified',
            'target_audience' => $briefing->target_audience ?? 'Not specified',
            'product_name' => $briefing->product_name ?? 'Not specified',
            'landing_page_url' => $briefing->landing_page_url ?? 'Not specified',
            'campaign_goal' => $briefing->campaign_goal ?? 'Not specified',
            'budget_amount' => $briefing->budget_amount ? '$' . number_format($briefing->budget_amount, 2) : 'Not specified',
            'notes' => $briefing->notes ?? 'None',
        ];
    }

    /**
     * Build prompt context for campaign draft
     *
     * @param CampaignDraft $draft
     * @return array
     */
    public function buildForDraft(CampaignDraft $draft): array
    {
        $briefing = $draft->briefing;
        $context = $this->buildForBriefing($briefing);

        // Add current draft payload
        $context['current_draft'] = $draft->draft_payload_json ?? [];
        $context['draft_name'] = $draft->generated_name ?? 'Unnamed draft';

        // Extract existing copy if available
        if (isset($draft->draft_payload_json['copy'])) {
            $context['current_copy'] = json_encode($draft->draft_payload_json['copy'], JSON_PRETTY_PRINT);
        }

        return $context;
    }
}
