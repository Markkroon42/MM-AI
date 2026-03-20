<?php

namespace App\Services\CampaignDraft;

use App\Models\CampaignBriefing;
use App\Models\CampaignTemplate;
use App\Models\UtmTemplate;
use Illuminate\Support\Facades\Log;

class UtmGeneratorService
{
    /**
     * Generate UTM parameters from template and briefing
     * Fix #3: Accept explicit campaign template for interpolation context
     */
    public function generate(
        UtmTemplate $template,
        CampaignBriefing $briefing,
        string $campaignName,
        ?CampaignTemplate $campaignTemplate = null
    ): array {
        Log::info('[UTM_GENERATOR] Generating UTM parameters', [
            'template_id' => $template->id,
            'briefing_id' => $briefing->id,
            'campaign_name' => $campaignName,
            'campaign_template_id' => $campaignTemplate?->id,
        ]);

        $replacements = $this->buildReplacements($briefing, $campaignName, $campaignTemplate);

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
     * Fix #3: Support all common placeholders (lowercase and uppercase)
     */
    protected function buildReplacements(
        CampaignBriefing $briefing,
        string $campaignName,
        ?CampaignTemplate $campaignTemplate = null
    ): array {
        $brand = strtolower($briefing->brand);
        $market = strtolower($briefing->market);
        $objective = strtolower($briefing->objective);
        $funnel = $campaignTemplate ? strtolower($campaignTemplate->funnel_stage) : 'unknown';
        $theme = $campaignTemplate && $campaignTemplate->theme ? strtolower($campaignTemplate->theme) : '';
        $campaignNameLower = strtolower(str_replace(' ', '_', $campaignName));

        return [
            // Lowercase variants (primary)
            '{brand}' => $brand,
            '{market}' => $market,
            '{funnel}' => $funnel,
            '{objective}' => $objective,
            '{theme}' => $theme,
            '{campaign_name}' => $campaignNameLower,
            '{yyyymm}' => now()->format('Ym'),
            '{yyyy}' => now()->format('Y'),
            '{mm}' => now()->format('m'),
            // Uppercase variants (backward compatibility)
            '{BRAND}' => $brand,
            '{MARKET}' => $market,
            '{FUNNEL}' => $funnel,
            '{OBJECTIVE}' => $objective,
            '{THEME}' => $theme,
            '{CAMPAIGN_NAME}' => $campaignNameLower,
            '{YYYYMM}' => now()->format('Ym'),
            '{YYYY}' => now()->format('Y'),
            '{MM}' => now()->format('m'),
            // Additional placeholders (with safe fallbacks)
            '{creative_type}' => '{creative_type}', // Keep for later substitution
            '{angle}' => '{angle}',
            '{variant}' => '{variant}',
            '{audience}' => '{audience}',
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
