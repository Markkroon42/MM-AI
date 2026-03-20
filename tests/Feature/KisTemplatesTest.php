<?php

namespace Tests\Feature;

use App\Models\CampaignTemplate;
use App\Models\UtmTemplate;
use Database\Seeders\KisTemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KisTemplatesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the KIS UTM template is created correctly.
     */
    public function test_kis_utm_template_is_created(): void
    {
        // Run the seeder
        $this->seed(KisTemplatesSeeder::class);

        // Assert UTM template exists
        $utmTemplate = UtmTemplate::where('name', 'KIS - Besparings Calculator - Meta')->first();

        $this->assertNotNull($utmTemplate);
        $this->assertEquals('meta', $utmTemplate->source);
        $this->assertEquals('paid_social', $utmTemplate->medium);
        $this->assertEquals('{brand}_{market}_{funnel}_{objective}_{theme}_{yyyymm}', $utmTemplate->campaign_pattern);
        $this->assertEquals('{creative_type}_{angle}_{variant}', $utmTemplate->content_pattern);
        $this->assertEquals('{audience}', $utmTemplate->term_pattern);
        $this->assertTrue($utmTemplate->is_active);
    }

    /**
     * Test that the KIS campaign template is created correctly.
     */
    public function test_kis_campaign_template_is_created(): void
    {
        // Run the seeder
        $this->seed(KisTemplatesSeeder::class);

        // Assert campaign template exists
        $campaignTemplate = CampaignTemplate::where('name', 'KIS Besparings Calculator – Prospecting')->first();

        $this->assertNotNull($campaignTemplate);
        $this->assertEquals('KIS', $campaignTemplate->brand);
        $this->assertEquals('NL', $campaignTemplate->market);
        $this->assertEquals('leads', $campaignTemplate->objective);
        $this->assertEquals('prospecting', $campaignTemplate->funnel_stage);
        $this->assertEquals('besparingscalculator', $campaignTemplate->theme);
        $this->assertEquals('50.00', $campaignTemplate->default_budget);
        $this->assertEquals('https://besparing.kis-haircare.nl/', $campaignTemplate->landing_page_url);
        $this->assertTrue($campaignTemplate->is_active);
    }

    /**
     * Test that the campaign template is linked to the correct UTM template.
     */
    public function test_campaign_template_is_linked_to_utm_template(): void
    {
        // Run the seeder
        $this->seed(KisTemplatesSeeder::class);

        // Get templates
        $utmTemplate = UtmTemplate::where('name', 'KIS - Besparings Calculator - Meta')->first();
        $campaignTemplate = CampaignTemplate::where('name', 'KIS Besparings Calculator – Prospecting')->first();

        $this->assertNotNull($campaignTemplate->default_utm_template_id);
        $this->assertEquals($utmTemplate->id, $campaignTemplate->default_utm_template_id);

        // Test relationship
        $this->assertNotNull($campaignTemplate->utmTemplate);
        $this->assertEquals('KIS - Besparings Calculator - Meta', $campaignTemplate->utmTemplate->name);
    }

    /**
     * Test that the campaign template has the correct structure.
     */
    public function test_campaign_template_has_correct_structure(): void
    {
        // Run the seeder
        $this->seed(KisTemplatesSeeder::class);

        $campaignTemplate = CampaignTemplate::where('name', 'KIS Besparings Calculator – Prospecting')->first();

        // Assert structure_json exists and has correct structure
        $this->assertNotNull($campaignTemplate->structure_json);
        $this->assertIsArray($campaignTemplate->structure_json);

        // Check campaign structure
        $this->assertArrayHasKey('campaign', $campaignTemplate->structure_json);
        $this->assertEquals('leads', $campaignTemplate->structure_json['campaign']['objective']);
        $this->assertEquals('besparingscalculator', $campaignTemplate->structure_json['campaign']['theme']);
        $this->assertEquals('https://besparing.kis-haircare.nl/', $campaignTemplate->structure_json['campaign']['landing_page_url']);

        // Check ad sets
        $this->assertArrayHasKey('ad_sets', $campaignTemplate->structure_json);
        $this->assertCount(2, $campaignTemplate->structure_json['ad_sets']);
        $this->assertEquals('BROAD_SALONOWNERS_LEADS', $campaignTemplate->structure_json['ad_sets'][0]['name']);
        $this->assertEquals('INTEREST_HAIRPROFESSIONALS_LEADS', $campaignTemplate->structure_json['ad_sets'][1]['name']);

        // Check ads
        $this->assertArrayHasKey('ads', $campaignTemplate->structure_json);
        $this->assertCount(3, $campaignTemplate->structure_json['ads']);
        $this->assertEquals('VIDEO_BESPARING_V1', $campaignTemplate->structure_json['ads'][0]['name']);
        $this->assertEquals('STATIC_SALONVOORDEEL_V1', $campaignTemplate->structure_json['ads'][1]['name']);
        $this->assertEquals('UGC_OVERSTAPPEN_V1', $campaignTemplate->structure_json['ads'][2]['name']);
    }

    /**
     * Test that the campaign template has creative rules.
     */
    public function test_campaign_template_has_creative_rules(): void
    {
        // Run the seeder
        $this->seed(KisTemplatesSeeder::class);

        $campaignTemplate = CampaignTemplate::where('name', 'KIS Besparings Calculator – Prospecting')->first();

        // Assert creative_rules_json exists
        $this->assertNotNull($campaignTemplate->creative_rules_json);
        $this->assertIsArray($campaignTemplate->creative_rules_json);

        // Check messaging angles
        $this->assertArrayHasKey('messaging_angles', $campaignTemplate->creative_rules_json);
        $this->assertCount(3, $campaignTemplate->creative_rules_json['messaging_angles']);
        $this->assertEquals('Besparing', $campaignTemplate->creative_rules_json['messaging_angles'][0]['name']);
        $this->assertEquals('Salonrendement', $campaignTemplate->creative_rules_json['messaging_angles'][1]['name']);
        $this->assertEquals('Overstappen / Vergelijking', $campaignTemplate->creative_rules_json['messaging_angles'][2]['name']);

        // Check recommended formats
        $this->assertArrayHasKey('recommended_formats', $campaignTemplate->creative_rules_json);
        $this->assertContains('video', $campaignTemplate->creative_rules_json['recommended_formats']);
        $this->assertContains('static', $campaignTemplate->creative_rules_json['recommended_formats']);
        $this->assertContains('ugc', $campaignTemplate->creative_rules_json['recommended_formats']);

        // Check CTAs
        $this->assertArrayHasKey('recommended_ctas', $campaignTemplate->creative_rules_json);
        $this->assertContains('Bereken je voordeel', $campaignTemplate->creative_rules_json['recommended_ctas']);
    }

    /**
     * Test that seeder is idempotent - running twice doesn't create duplicates.
     */
    public function test_seeder_is_idempotent(): void
    {
        // Run seeder twice
        $this->seed(KisTemplatesSeeder::class);
        $this->seed(KisTemplatesSeeder::class);

        // Assert only one UTM template exists
        $utmCount = UtmTemplate::where('name', 'KIS - Besparings Calculator - Meta')->count();
        $this->assertEquals(1, $utmCount);

        // Assert only one campaign template exists
        $campaignCount = CampaignTemplate::where('name', 'KIS Besparings Calculator – Prospecting')->count();
        $this->assertEquals(1, $campaignCount);
    }

    /**
     * Test that templates are active and ready for use.
     */
    public function test_templates_are_active(): void
    {
        // Run the seeder
        $this->seed(KisTemplatesSeeder::class);

        $utmTemplate = UtmTemplate::where('name', 'KIS - Besparings Calculator - Meta')->first();
        $campaignTemplate = CampaignTemplate::where('name', 'KIS Besparings Calculator – Prospecting')->first();

        $this->assertTrue($utmTemplate->is_active);
        $this->assertTrue($campaignTemplate->is_active);
    }

    /**
     * Test that the template is available in the builder via CampaignTemplateService.
     */
    public function test_template_is_available_in_builder(): void
    {
        // Run the seeder
        $this->seed(KisTemplatesSeeder::class);

        // Use the service that the builder uses
        $service = new \App\Services\CampaignDraft\CampaignTemplateService();
        $activeTemplates = $service->getActiveTemplates();

        // Assert KIS template is in the list
        $kisTemplate = $activeTemplates->firstWhere('name', 'KIS Besparings Calculator – Prospecting');
        $this->assertNotNull($kisTemplate);

        // Assert UTM template is loaded via relationship
        $this->assertNotNull($kisTemplate->utmTemplate);
        $this->assertEquals('KIS - Besparings Calculator - Meta', $kisTemplate->utmTemplate->name);
    }

    /**
     * Test that template structure can be applied to briefing data.
     */
    public function test_template_structure_can_be_applied_to_briefing(): void
    {
        // Run the seeder
        $this->seed(KisTemplatesSeeder::class);

        $campaignTemplate = CampaignTemplate::where('name', 'KIS Besparings Calculator – Prospecting')->first();

        // Simulate briefing data
        $briefingData = [
            'objective' => 'leads',
            'budget_amount' => 75.00,
            'target_audience' => 'Hair salon owners in NL',
            'landing_page_url' => 'https://besparing.kis-haircare.nl/',
        ];

        // Apply template
        $service = new \App\Services\CampaignDraft\CampaignTemplateService();
        $appliedStructure = $service->applyTemplate($campaignTemplate, $briefingData);

        // Assert structure is applied correctly
        $this->assertArrayHasKey('campaign', $appliedStructure);
        $this->assertArrayHasKey('ad_sets', $appliedStructure);
        $this->assertArrayHasKey('ads', $appliedStructure);
        $this->assertArrayHasKey('creative_rules', $appliedStructure);

        // Assert briefing data overrides template defaults
        $this->assertEquals('leads', $appliedStructure['campaign']['objective']);
        $this->assertEquals(75.00, $appliedStructure['campaign']['daily_budget']);

        // Assert ad sets are present
        $this->assertCount(2, $appliedStructure['ad_sets']);

        // Assert ads are present with landing page
        $this->assertCount(3, $appliedStructure['ads']);
        $this->assertEquals('https://besparing.kis-haircare.nl/', $appliedStructure['ads'][0]['creative']['link_url']);

        // Assert creative rules are included
        $this->assertArrayHasKey('messaging_angles', $appliedStructure['creative_rules']);
        $this->assertCount(3, $appliedStructure['creative_rules']['messaging_angles']);
    }
}
