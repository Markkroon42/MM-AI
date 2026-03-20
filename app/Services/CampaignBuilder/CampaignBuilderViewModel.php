<?php

namespace App\Services\CampaignBuilder;

use App\Models\CampaignBriefing;
use App\Models\CampaignDraft;
use App\Models\CampaignTemplate;

class CampaignBuilderViewModel
{
    public function __construct(
        protected CampaignReadinessService $readinessService,
        protected DraftValidationService $validationService,
        protected CampaignStructurePresenter $structurePresenter
    ) {
    }

    public function buildForDraft(CampaignDraft $draft): array
    {
        $readiness = $this->readinessService->calculateReadiness($draft);
        $validation = $this->validationService->validate($draft);
        $structure = $this->structurePresenter->present($draft);

        return [
            'draft' => $draft,
            'readiness' => $readiness,
            'validation' => $validation,
            'structure' => $structure,
            'briefing' => $draft->briefing,
            'template' => $draft->template,
            'enrichments' => $this->getEnrichmentsSummary($draft),
            'approval_status' => $this->getApprovalStatus($draft),
        ];
    }

    public function buildForBriefing(CampaignBriefing $briefing, ?CampaignTemplate $template = null): array
    {
        return [
            'briefing' => $briefing,
            'template' => $template,
            'suggested_templates' => $this->getSuggestedTemplates($briefing),
            'has_drafts' => $briefing->campaignDrafts()->exists(),
        ];
    }

    protected function getEnrichmentsSummary(CampaignDraft $draft): array
    {
        $enrichments = $draft->draftEnrichments;

        return [
            'copy_count' => $enrichments->where('enrichment_type', 'copy_variants')->count(),
            'creative_count' => $enrichments->where('enrichment_type', 'creative_suggestions')->count(),
            'strategy_count' => $enrichments->where('enrichment_type', 'strategy')->count(),
            'total_count' => $enrichments->count(),
            'latest' => $enrichments->sortByDesc('created_at')->take(3),
        ];
    }

    protected function getApprovalStatus(CampaignDraft $draft): array
    {
        $latestApproval = $draft->approvals()->latest()->first();

        return [
            'has_approval' => $latestApproval !== null,
            'latest' => $latestApproval,
            'approved' => $draft->status === 'approved',
            'pending' => $draft->status === 'ready_for_review',
        ];
    }

    protected function getSuggestedTemplates(CampaignBriefing $briefing): array
    {
        return CampaignTemplate::query()
            ->where('is_active', true)
            ->where(function ($query) use ($briefing) {
                $query->where('brand', $briefing->brand)
                      ->orWhereNull('brand');
            })
            ->where(function ($query) use ($briefing) {
                $query->where('market', $briefing->market)
                      ->orWhereNull('market');
            })
            ->where(function ($query) use ($briefing) {
                $query->where('objective', $briefing->objective)
                      ->orWhereNull('objective');
            })
            ->get()
            ->toArray();
    }
}
