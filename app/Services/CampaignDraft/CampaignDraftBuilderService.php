<?php

namespace App\Services\CampaignDraft;

use App\Enums\CampaignBriefingStatusEnum;
use App\Enums\CampaignDraftStatusEnum;
use App\Models\AuditLog;
use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;
use App\Models\CampaignTemplate;
use Illuminate\Support\Facades\Log;

class CampaignDraftBuilderService
{
    public function __construct(
        protected CampaignTemplateService $templateService,
        protected UtmGeneratorService $utmGenerator
    ) {}

    /**
     * Build campaign draft from briefing and template
     */
    public function buildFromBriefing(
        CampaignBriefing $briefing,
        CampaignTemplate $template
    ): CampaignDraft {
        Log::info('[DRAFT_BUILDER] Building draft from briefing', [
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
        ]);

        // Generate campaign name (Fix #4: include theme)
        $campaignName = $this->generateCampaignName(
            $template->brand,
            $template->market,
            $template->funnel_stage,
            $template->objective,
            $template->theme
        );

        Log::info('[DRAFT_BUILDER] Generated campaign name', [
            'campaign_name' => $campaignName,
            'theme_used' => $template->theme ?? 'none',
        ]);

        // Apply template structure
        $briefingData = [
            'brand' => $briefing->brand,
            'market' => $briefing->market,
            'objective' => $briefing->objective,
            'product_name' => $briefing->product_name,
            'target_audience' => $briefing->target_audience,
            'landing_page_url' => $briefing->landing_page_url,
            'budget_amount' => $briefing->budget_amount,
            'campaign_goal' => $briefing->campaign_goal,
        ];

        $structure = $this->templateService->applyTemplate($template, $briefingData);

        // Generate UTM parameters if template has UTM template
        // Fix #3: Pass campaign template for complete interpolation context
        $utmParameters = [];
        if ($template->utmTemplate) {
            $utmParameters = $this->utmGenerator->generate(
                $template->utmTemplate,
                $briefing,
                $campaignName,
                $template
            );

            // Apply UTM parameters to landing page URL
            if (!empty($structure['ads'])) {
                foreach ($structure['ads'] as &$ad) {
                    if (isset($ad['creative']['link_url'])) {
                        $ad['creative']['link_url'] = $this->appendUtmParameters(
                            $ad['creative']['link_url'],
                            $utmParameters
                        );
                    }
                }
            }
        }

        // Build draft payload (Fix #1: ensure campaign.name is set in payload)
        $draftPayload = array_merge($structure, [
            'campaign' => array_merge($structure['campaign'] ?? [], [
                'name' => $campaignName,
            ]),
            'briefing' => $briefingData,
            'utm_parameters' => $utmParameters,
        ]);

        Log::info('[DRAFT_BUILDER] Campaign name set in payload', [
            'payload_campaign_name' => $draftPayload['campaign']['name'],
            'generated_name' => $campaignName,
        ]);

        // Create draft
        $draft = CampaignDraft::create([
            'briefing_id' => $briefing->id,
            'template_id' => $template->id,
            'generated_name' => $campaignName,
            'draft_payload_json' => $draftPayload,
            'status' => CampaignDraftStatusEnum::DRAFT->value,
        ]);

        // Update briefing status
        $briefing->update([
            'status' => CampaignBriefingStatusEnum::GENERATED->value,
        ]);

        AuditLog::log(
            'draft_generated',
            $draft,
            null,
            [
                'briefing_id' => $briefing->id,
                'template_id' => $template->id,
                'campaign_name' => $campaignName,
            ]
        );

        Log::info('[DRAFT_BUILDER] Draft created successfully', [
            'draft_id' => $draft->id,
            'campaign_name' => $campaignName,
        ]);

        return $draft;
    }

    /**
     * Generate campaign name using naming pattern
     * Fix #4: Include theme in campaign name
     */
    protected function generateCampaignName(
        string $brand,
        string $market,
        string $funnelStage,
        string $objective,
        ?string $theme = null
    ): string {
        $parts = [
            strtoupper($brand),
            strtoupper($market),
            strtoupper($funnelStage),
            strtoupper($objective),
        ];

        // Add theme if available
        if ($theme) {
            $parts[] = strtoupper($theme);
        }

        // Add date
        $parts[] = now()->format('Ym');

        return implode('_', $parts);
    }

    /**
     * Append UTM parameters to URL
     */
    protected function appendUtmParameters(string $url, array $utmParameters): string
    {
        $query = http_build_query($utmParameters);
        $separator = parse_url($url, PHP_URL_QUERY) ? '&' : '?';

        return $url . $separator . $query;
    }
}
