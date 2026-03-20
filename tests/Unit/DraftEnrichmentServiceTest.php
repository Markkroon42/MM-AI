<?php

namespace Tests\Unit;

use App\Enums\DraftEnrichmentStatusEnum;
use App\Enums\DraftEnrichmentTypeEnum;
use App\Models\CampaignDraft;
use App\Models\DraftEnrichment;
use App\Models\User;
use App\Services\AI\DraftEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftEnrichmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DraftEnrichmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DraftEnrichmentService();
    }

    public function test_stores_enrichment(): void
    {
        $draft = CampaignDraft::factory()->create();
        $user = User::factory()->create();

        $enrichment = $this->service->storeEnrichment(
            $draft,
            DraftEnrichmentTypeEnum::COPY_VARIANTS,
            ['test' => 'payload'],
            null,
            $user
        );

        $this->assertInstanceOf(DraftEnrichment::class, $enrichment);
        $this->assertEquals($draft->id, $enrichment->campaign_draft_id);
        $this->assertEquals(DraftEnrichmentTypeEnum::COPY_VARIANTS->value, $enrichment->enrichment_type);
        $this->assertEquals(DraftEnrichmentStatusEnum::DRAFT->value, $enrichment->status);
    }

    public function test_approves_enrichment(): void
    {
        $enrichment = DraftEnrichment::factory()->create([
            'status' => DraftEnrichmentStatusEnum::DRAFT->value,
        ]);
        $user = User::factory()->create();

        $this->service->approveEnrichment($enrichment, $user);

        $enrichment->refresh();
        $this->assertEquals(DraftEnrichmentStatusEnum::APPROVED->value, $enrichment->status);
    }

    public function test_applies_enrichment_to_draft_safely(): void
    {
        $draft = CampaignDraft::factory()->create([
            'draft_payload_json' => ['existing' => 'data'],
        ]);
        $enrichment = DraftEnrichment::factory()->create([
            'campaign_draft_id' => $draft->id,
            'enrichment_type' => DraftEnrichmentTypeEnum::COPY_VARIANTS->value,
            'payload_json' => ['copy' => 'variants'],
            'status' => DraftEnrichmentStatusEnum::APPROVED->value,
        ]);
        $user = User::factory()->create();

        $this->service->applyEnrichment($enrichment, $user);

        $draft->refresh();
        $enrichment->refresh();

        // Check that original data is preserved
        $this->assertArrayHasKey('existing', $draft->draft_payload_json);
        $this->assertEquals('data', $draft->draft_payload_json['existing']);

        // Check that enrichment is added to ai_enrichments section
        $this->assertArrayHasKey('ai_enrichments', $draft->draft_payload_json);
        $this->assertArrayHasKey('copy_variants', $draft->draft_payload_json['ai_enrichments']);

        // Check enrichment status is updated
        $this->assertEquals(DraftEnrichmentStatusEnum::APPLIED->value, $enrichment->status);
    }
}
