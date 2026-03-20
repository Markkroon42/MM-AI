<?php

namespace Database\Seeders;

use App\Models\CampaignTemplate;
use App\Models\UtmTemplate;
use Illuminate\Database\Seeder;

class KisTemplatesSeeder extends Seeder
{
    /**
     * Seed KIS Haircare Besparings Calculator templates.
     * This seeder is idempotent - safe to run multiple times.
     */
    public function run(): void
    {
        // Create or update UTM template for KIS Besparings Calculator - Meta
        $utmTemplate = UtmTemplate::updateOrCreate(
            ['name' => 'KIS - Besparings Calculator - Meta'],
            [
                'source' => 'meta',
                'medium' => 'paid_social',
                'campaign_pattern' => '{brand}_{market}_{funnel}_{objective}_{theme}_{yyyymm}',
                'content_pattern' => '{creative_type}_{angle}_{variant}',
                'term_pattern' => '{audience}',
                'is_active' => true,
            ]
        );

        $this->command->info("✓ UTM Template created/updated: {$utmTemplate->name}");

        // Define campaign structure JSON
        $structureJson = [
            'campaign' => [
                'naming_pattern' => '{brand}_{market}_{funnel}_{objective}_{theme}_{yyyymm}',
                'objective' => 'leads',
                'theme' => 'besparingscalculator',
                'status' => 'draft',
                'landing_page_url' => 'https://besparing.kis-haircare.nl/',
            ],
            'ad_sets' => [
                [
                    'name' => 'BROAD_SALONOWNERS_LEADS',
                    'audience' => 'broad_salonowners',
                    'budget_mode' => 'default',
                    'placements' => 'advantage_plus',
                    'optimization_goal' => 'leads',
                ],
                [
                    'name' => 'INTEREST_HAIRPROFESSIONALS_LEADS',
                    'audience' => 'interest_hairprofessionals',
                    'budget_mode' => 'default',
                    'placements' => 'advantage_plus',
                    'optimization_goal' => 'leads',
                ],
            ],
            'ads' => [
                [
                    'name' => 'VIDEO_BESPARING_V1',
                    'creative_type' => 'video',
                    'angle' => 'besparing',
                    'landing_page_url' => 'https://besparing.kis-haircare.nl/',
                ],
                [
                    'name' => 'STATIC_SALONVOORDEEL_V1',
                    'creative_type' => 'static',
                    'angle' => 'salonvoordeel',
                    'landing_page_url' => 'https://besparing.kis-haircare.nl/',
                ],
                [
                    'name' => 'UGC_OVERSTAPPEN_V1',
                    'creative_type' => 'ugc',
                    'angle' => 'overstappen',
                    'landing_page_url' => 'https://besparing.kis-haircare.nl/',
                ],
            ],
        ];

        // Define creative rules / strategy defaults
        $creativeRulesJson = [
            'messaging_angles' => [
                [
                    'name' => 'Besparing',
                    'description' => 'Ontdek je financiële voordeel binnen 1 minuint',
                    'key_messages' => [
                        'Hoeveel kun je besparen op haarverf?',
                        'Direct inzicht in je voordeel',
                        'Ontdek binnen 1 minuut je financiële voordeel op haarverf',
                        'Wees voorbereid, want jouw voordeel is gegarandeerd GROOT',
                    ],
                ],
                [
                    'name' => 'Salonrendement',
                    'description' => 'Focus op zakelijke voordelen voor salons',
                    'key_messages' => [
                        'Verbeter je salonmarge',
                        'Meer rendement op kleurbehandelingen',
                        'Slimmer inkopen en calculeren',
                    ],
                ],
                [
                    'name' => 'Overstappen / Vergelijking',
                    'description' => 'Stimuleer switch-intent door vergelijking',
                    'key_messages' => [
                        'Gebruik je nu een ander merk?',
                        'Bereken wat overstappen oplevert',
                        'Vergelijk je huidige kosten met KIS',
                    ],
                ],
            ],
            'recommended_formats' => [
                'video',
                'static',
                'ugc',
            ],
            'recommended_ctas' => [
                'Bereken je voordeel',
                'Ontdek je besparing',
                'Start de calculator',
                'Vergelijk direct',
            ],
            'target_audience' => [
                'Salons / professionals',
                'Hairstylists',
                'Salonowners',
            ],
            'campaign_goal' => 'Leadgeneratie via besparingscalculator',
        ];

        // Create or update Campaign Template
        $campaignTemplate = CampaignTemplate::updateOrCreate(
            ['name' => 'KIS Besparings Calculator – Prospecting'],
            [
                'brand' => 'KIS',
                'market' => 'NL',
                'objective' => 'leads',
                'funnel_stage' => 'prospecting',
                'theme' => 'besparingscalculator',
                'default_budget' => 50.00,
                'default_utm_template_id' => $utmTemplate->id,
                'landing_page_url' => 'https://besparing.kis-haircare.nl/',
                'structure_json' => $structureJson,
                'creative_rules_json' => $creativeRulesJson,
                'is_active' => true,
            ]
        );

        $this->command->info("✓ Campaign Template created/updated: {$campaignTemplate->name}");
        $this->command->info("  - Brand: {$campaignTemplate->brand}");
        $this->command->info("  - Market: {$campaignTemplate->market}");
        $this->command->info("  - Objective: {$campaignTemplate->objective}");
        $this->command->info("  - Funnel Stage: {$campaignTemplate->funnel_stage}");
        $this->command->info("  - Theme: {$campaignTemplate->theme}");
        $this->command->info("  - Landing Page: {$campaignTemplate->landing_page_url}");
        $this->command->info("  - Default Budget: €{$campaignTemplate->default_budget}");
        $this->command->info("  - Linked UTM Template: {$utmTemplate->name}");
        $this->command->info("  - Ad Sets: " . count($structureJson['ad_sets']));
        $this->command->info("  - Ads: " . count($structureJson['ads']));
    }
}
