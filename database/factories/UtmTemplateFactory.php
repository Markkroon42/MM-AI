<?php

namespace Database\Factories;

use App\Models\UtmTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class UtmTemplateFactory extends Factory
{
    protected $model = UtmTemplate::class;

    public function definition(): array
    {
        return [
            'name' => 'Test UTM Template',
            'source' => 'meta',
            'medium' => 'paid_social',
            'campaign_pattern' => '{brand}_{market}_{funnel}_{objective}_{yyyymm}',
            'content_pattern' => '{creative_type}_{angle}',
            'term_pattern' => '{audience}',
            'is_active' => true,
        ];
    }
}
