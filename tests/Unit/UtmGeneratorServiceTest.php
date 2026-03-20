<?php

namespace Tests\Unit;

use App\Models\CampaignBriefing;
use App\Models\UtmTemplate;
use App\Services\CampaignDraft\UtmGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UtmGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UtmGeneratorService $utmGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->utmGenerator = app(UtmGeneratorService::class);
    }

    public function test_generates_utm_parameters(): void
    {
        $template = UtmTemplate::factory()->create([
            'source' => 'facebook',
            'medium' => 'cpc',
            'campaign_pattern' => '{BRAND}_{MARKET}_{YYYYMM}',
        ]);

        $briefing = CampaignBriefing::factory()->create([
            'brand' => 'TestBrand',
            'market' => 'US',
        ]);

        $result = $this->utmGenerator->generate($template, $briefing, 'TestCampaign');

        $this->assertEquals('facebook', $result['utm_source']);
        $this->assertEquals('cpc', $result['utm_medium']);
        $this->assertStringContainsString('testbrand', $result['utm_campaign']);
        $this->assertStringContainsString('us', $result['utm_campaign']);
    }

    public function test_replaces_patterns_correctly(): void
    {
        $template = UtmTemplate::factory()->create([
            'source' => '{BRAND}',
            'medium' => 'social',
            'campaign_pattern' => '{CAMPAIGN_NAME}_{OBJECTIVE}',
        ]);

        $briefing = CampaignBriefing::factory()->create([
            'brand' => 'MyBrand',
            'objective' => 'Traffic',
        ]);

        $result = $this->utmGenerator->generate($template, $briefing, 'Spring_Sale');

        $this->assertEquals('mybrand', $result['utm_source']);
        $this->assertStringContainsString('spring_sale', $result['utm_campaign']);
        $this->assertStringContainsString('traffic', $result['utm_campaign']);
    }
}
