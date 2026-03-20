<?php

namespace Database\Factories;

use App\Enums\DraftEnrichmentStatusEnum;
use App\Enums\DraftEnrichmentTypeEnum;
use App\Models\CampaignDraft;
use App\Models\DraftEnrichment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DraftEnrichmentFactory extends Factory
{
    protected $model = DraftEnrichment::class;

    public function definition(): array
    {
        return [
            'campaign_draft_id' => CampaignDraft::factory(),
            'ai_usage_log_id' => null,
            'enrichment_type' => DraftEnrichmentTypeEnum::COPY_VARIANTS->value,
            'status' => DraftEnrichmentStatusEnum::DRAFT->value,
            'payload_json' => [
                'primary_texts' => ['Test primary text'],
                'headlines' => ['Test headline'],
                'descriptions' => ['Test description'],
            ],
            'created_by' => User::factory(),
        ];
    }
}
