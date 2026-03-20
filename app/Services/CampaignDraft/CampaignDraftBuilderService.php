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

        // Generate campaign name
        $campaignName = $this->generateCampaignName(
            $template->brand,
            $template->market,
            $template->funnel_stage,
            $template->objective
        );

        Log::info('[DRAFT_BUILDER] Generated campaign name', [
            'campaign_name' => $campaignName,
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
        $utmParameters = [];
        if ($template->utmTemplate) {
            $utmParameters = $this->utmGenerator->generate(
                $template->utmTemplate,
                $briefing,
                $campaignName
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

        // Build draft payload
        $draftPayload = array_merge($structure, [
            'name' => $campaignName,
            'briefing' => $briefingData,
            'utm_parameters' => $utmParameters,
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
     */
    protected function generateCampaignName(
        string $brand,
        string $market,
        string $funnelStage,
        string $objective
    ): string {
        $parts = [
            strtoupper($brand),
            strtoupper($market),
            strtoupper($funnelStage),
            strtoupper($objective),
            now()->format('Ym'),
        ];

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
