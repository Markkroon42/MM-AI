<?php

namespace Database\Factories;

use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;
use App\Models\CampaignTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignDraftFactory extends Factory
{
    protected $model = CampaignDraft::class;

    public function definition(): array
    {
        return [
            'briefing_id' => CampaignBriefing::factory(),
            'template_id' => CampaignTemplate::factory(),
            'generated_name' => 'KIS_NL_PROSPECTING_LEADS_202603',
            'draft_payload_json' => [
                'campaign' => [
                    'name' => 'KIS_NL_PROSPECTING_LEADS_202603',
                    'objective' => 'LEADS',
                    'daily_budget' => 50.00,
                    'status' => 'draft',
                    'landing_page_url' => 'https://besparing.kis-haircare.nl/',
                ],
                'ad_sets' => [
                    [
                        'name' => 'BROAD_SALONOWNERS_LEADS',
                        'audience' => 'broad_salonowners',
                        'budget_mode' => 'default',
                        'placements' => 'advantage_plus',
                        'optimization_goal' => 'LEADS',
                    ],
                    [
                        'name' => 'INTEREST_HAIRPROFESSIONALS_LEADS',
                        'audience' => 'interest_hairprofessionals',
                        'budget_mode' => 'default',
                        'placements' => 'advantage_plus',
                        'optimization_goal' => 'LEADS',
                    ],
                ],
                'ads' => [
                    [
                        'name' => 'VIDEO_BESPARING_V1',
                        'creative_type' => 'video',
                        'angle' => 'besparing',
                        'message' => 'Ontdek je financiële voordeel binnen 1 minuut',
                        'headline' => 'Bereken je voordeel',
                        'description' => 'Hoeveel kun je besparen op haarverf?',
                        'landing_page_url' => 'https://besparing.kis-haircare.nl/',
                        'utm_parameters' => [
                            'utm_source' => 'meta',
                            'utm_medium' => 'paid_social',
                            'utm_campaign' => 'kis_nl_prospecting_leads_202603',
                            'utm_content' => 'video_besparing_v1',
                        ],
                    ],
                    [
                        'name' => 'STATIC_SALONVOORDEEL_V1',
                        'creative_type' => 'static',
                        'angle' => 'salonvoordeel',
                        'message' => 'Verbeter je salonmarge met KIS',
                        'headline' => 'Meer rendement op kleurbehandelingen',
                        'description' => 'Slimmer inkopen en calculeren',
                        'landing_page_url' => 'https://besparing.kis-haircare.nl/',
                        'utm_parameters' => [
                            'utm_source' => 'meta',
                            'utm_medium' => 'paid_social',
                            'utm_campaign' => 'kis_nl_prospecting_leads_202603',
                            'utm_content' => 'static_salonvoordeel_v1',
                        ],
                    ],
                ],
            ],
            'status' => 'DRAFT',
            'approved_by' => null,
            'approved_at' => null,
            'review_notes' => null,
            'published_at' => null,
        ];
    }
}
