<?php

namespace Database\Factories;

use App\Models\CampaignBriefing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignBriefingFactory extends Factory
{
    protected $model = CampaignBriefing::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'brand' => 'KIS',
            'market' => 'NL',
            'objective' => 'LEADS',
            'product_name' => 'KIS Haircare',
            'target_audience' => 'Salon owners in Netherlands',
            'landing_page_url' => 'https://besparing.kis-haircare.nl/',
            'budget_amount' => 1000.00,
            'campaign_goal' => 'Generate leads for savings calculator',
            'notes' => null,
            'status' => 'draft',
        ];
    }
}
