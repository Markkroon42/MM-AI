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
                ],
                'ad_sets' => [],
                'ads' => [],
            ],
            'status' => 'DRAFT',
            'approved_by' => null,
            'approved_at' => null,
            'review_notes' => null,
            'published_at' => null,
        ];
    }
}
