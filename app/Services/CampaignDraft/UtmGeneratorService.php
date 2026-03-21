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
     * Fix Issue #2: Add slugging and resolve creative/audience placeholders
     */
    protected function buildReplacements(
        CampaignBriefing $briefing,
        string $campaignName,
        ?CampaignTemplate $campaignTemplate = null
    ): array {
        // Fix Issue #2: Slug values (replace spaces with underscores)
        $brand = $this->slug(strtolower($briefing->brand));
        $market = $this->slug(strtolower($briefing->market));
        $objective = $this->slug(strtolower($briefing->objective));
        $funnel = $campaignTemplate ? $this->slug(strtolower($campaignTemplate->funnel_stage)) : 'unknown';
        $theme = $campaignTemplate && $campaignTemplate->theme ? $this->slug(strtolower($campaignTemplate->theme)) : '';
        $campaignNameLower = $this->slug(strtolower($campaignName));

        Log::info('[UTM_GENERATOR] Slugged brand value', [
            'original' => $briefing->brand,
            'slugged' => $brand,
        ]);

        // Fix Issue #2: Resolve creative and audience placeholders with fallbacks
        $creativeType = $this->resolveCreativeType($campaignTemplate);
        $angle = $this->resolveAngle($campaignTemplate);
        $variant = 'v1'; // Default variant
        $audience = $this->resolveAudience($campaignTemplate);

        Log::info('[UTM_GENERATOR] Resolved utm_content placeholders', [
            'creative_type' => $creativeType,
            'angle' => $angle,
            'variant' => $variant,
        ]);

        Log::info('[UTM_GENERATOR] Resolved utm_term placeholder', [
            'audience' => $audience,
        ]);

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
            // Additional placeholders (Fix Issue #2: now resolved)
            '{creative_type}' => $creativeType,
            '{angle}' => $angle,
            '{variant}' => $variant,
            '{audience}' => $audience,
        ];
    }

    /**
     * Slug a string (replace spaces and special chars with underscores)
     * Fix Issue #2
     */
    protected function slug(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '_', strtolower(trim($value)));
    }

    /**
     * Resolve creative_type from template structure
     * Fix Issue #2
     */
    protected function resolveCreativeType(?CampaignTemplate $template): string
    {
        if (!$template || empty($template->structure_json['ads'])) {
            return 'generic';
        }

        $firstAd = $template->structure_json['ads'][0] ?? null;
        if ($firstAd && isset($firstAd['creative_type'])) {
            return $this->slug($firstAd['creative_type']);
        }

        return 'generic';
    }

    /**
     * Resolve angle from template structure
     * Fix Issue #2
     */
    protected function resolveAngle(?CampaignTemplate $template): string
    {
        if (!$template) {
            return 'default';
        }

        // Try to get from template theme or first ad
        if ($template->theme) {
            return $this->slug($template->theme);
        }

        if (!empty($template->structure_json['ads'])) {
            $firstAd = $template->structure_json['ads'][0] ?? null;
            if ($firstAd && isset($firstAd['angle'])) {
                return $this->slug($firstAd['angle']);
            }
        }

        return 'default';
    }

    /**
     * Resolve audience from template structure
     * Fix Issue #2
     */
    protected function resolveAudience(?CampaignTemplate $template): string
    {
        if (!$template || empty($template->structure_json['ad_sets'])) {
            return 'broad_default';
        }

        $firstAdSet = $template->structure_json['ad_sets'][0] ?? null;
        if ($firstAdSet && isset($firstAdSet['audience'])) {
            return $this->slug($firstAdSet['audience']);
        }

        return 'broad_default';
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
