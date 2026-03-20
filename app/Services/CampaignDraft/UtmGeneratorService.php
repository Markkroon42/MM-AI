<?php

namespace App\Services\CampaignDraft;

use App\Models\CampaignBriefing;
use App\Models\UtmTemplate;
use Illuminate\Support\Facades\Log;

class UtmGeneratorService
{
    /**
     * Generate UTM parameters from template and briefing
     */
    public function generate(
        UtmTemplate $template,
        CampaignBriefing $briefing,
        string $campaignName
    ): array {
        Log::info('[UTM_GENERATOR] Generating UTM parameters', [
            'template_id' => $template->id,
            'briefing_id' => $briefing->id,
            'campaign_name' => $campaignName,
        ]);

        $replacements = $this->buildReplacements($briefing, $campaignName);

        $utmParameters = [
            'utm_source' => $this->replacePatterns($template->source, $replacements),
            'utm_medium' => $this->replacePatterns($template->medium, $replacements),
            'utm_campaign' => $this->replacePatterns($template->campaign_pattern, $replacements),
        ];

        if ($template->content_pattern) {
            $utmParameters['utm_content'] = $this->replacePatterns($template->content_pattern, $replacements);
        }

        if ($template->term_pattern) {
            $utmParameters['utm_term'] = $this->replacePatterns($template->term_pattern, $replacements);
        }

        Log::info('[UTM_GENERATOR] UTM parameters generated', [
            'utm_parameters' => $utmParameters,
        ]);

        return $utmParameters;
    }

    /**
     * Build replacement patterns
     */
    protected function buildReplacements(CampaignBriefing $briefing, string $campaignName): array
    {
        return [
            '{BRAND}' => strtolower($briefing->brand),
            '{MARKET}' => strtolower($briefing->market),
            '{CAMPAIGN_NAME}' => strtolower(str_replace(' ', '_', $campaignName)),
            '{OBJECTIVE}' => strtolower($briefing->objective),
            '{YYYYMM}' => now()->format('Ym'),
            '{YYYY}' => now()->format('Y'),
            '{MM}' => now()->format('m'),
        ];
    }

    /**
     * Replace patterns in string
     */
    protected function replacePatterns(string $pattern, array $replacements): string
    {
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $pattern
        );
    }
}
