<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DraftEnrichment;
use App\Services\AI\DraftEnrichmentService;
use Illuminate\Http\Request;

class DraftEnrichmentController extends Controller
{
    protected DraftEnrichmentService $enrichmentService;

    public function __construct(DraftEnrichmentService $enrichmentService)
    {
        $this->enrichmentService = $enrichmentService;
    }

    public function approve(DraftEnrichment $enrichment)
    {
        try {
            $this->enrichmentService->approveEnrichment($enrichment, auth()->user());

            return redirect()
                ->route('admin.campaign-drafts.show', $enrichment->campaign_draft_id)
                ->with('success', 'Enrichment approved successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.campaign-drafts.show', $enrichment->campaign_draft_id)
                ->with('error', 'Failed to approve enrichment: ' . $e->getMessage());
        }
    }

    public function reject(DraftEnrichment $enrichment)
    {
        try {
            $this->enrichmentService->rejectEnrichment($enrichment, auth()->user());

            return redirect()
                ->route('admin.campaign-drafts.show', $enrichment->campaign_draft_id)
                ->with('success', 'Enrichment rejected.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.campaign-drafts.show', $enrichment->campaign_draft_id)
                ->with('error', 'Failed to reject enrichment: ' . $e->getMessage());
        }
    }

    public function apply(DraftEnrichment $enrichment)
    {
        try {
            $this->enrichmentService->applyEnrichment($enrichment, auth()->user());

            return redirect()
                ->route('admin.campaign-drafts.show', $enrichment->campaign_draft_id)
                ->with('success', 'Enrichment applied to draft successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.campaign-drafts.show', $enrichment->campaign_draft_id)
                ->with('error', 'Failed to apply enrichment: ' . $e->getMessage());
        }
    }
}
