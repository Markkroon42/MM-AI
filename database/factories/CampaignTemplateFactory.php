<?php

namespace Database\Factories;

use App\Models\CampaignTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignTemplateFactory extends Factory
{
    protected $model = CampaignTemplate::class;

    public function definition(): array
    {
        return [
            'name' => 'KIS NL Prospecting Leads Template',
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'LEADS',
            'funnel_stage' => 'PROSPECTING',
            'theme' => null,
            'default_budget' => 50.00,
            'default_utm_template_id' => null,
            'landing_page_url' => 'https://besparing.kis-haircare.nl/',
            'structure_json' => [
                'campaign' => [
                    'objective' => 'LEADS',
                    'status' => 'PAUSED',
                ],
                'ad_sets' => [],
                'ads' => [],
            ],
            'creative_rules_json' => [],
            'is_active' => true,
        ];
    }
}
