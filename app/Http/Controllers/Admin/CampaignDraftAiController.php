<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignDraft;
use App\Services\AI\CopyAgentService;
use App\Services\AI\CreativeSuggestionAgentService;
use Illuminate\Http\Request;

class CampaignDraftAiController extends Controller
{
    protected CopyAgentService $copyService;
    protected CreativeSuggestionAgentService $creativeService;

    public function __construct(
        CopyAgentService $copyService,
        CreativeSuggestionAgentService $creativeService
    ) {
        $this->copyService = $copyService;
        $this->creativeService = $creativeService;
    }

    public function generateCopy(CampaignDraft $draft)
    {
        try {
            $enrichment = $this->copyService->generateForDraft($draft);

            return redirect()
                ->route('admin.campaign-drafts.show', $draft)
                ->with('success', 'Copy variants generated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.campaign-drafts.show', $draft)
                ->with('error', 'Failed to generate copy: ' . $e->getMessage());
        }
    }

    public function generateCreative(CampaignDraft $draft)
    {
        try {
            $enrichment = $this->creativeService->generateForDraft($draft);

            return redirect()
                ->route('admin.campaign-drafts.show', $draft)
                ->with('success', 'Creative suggestions generated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.campaign-drafts.show', $draft)
                ->with('error', 'Failed to generate creative suggestions: ' . $e->getMessage());
        }
    }

    public function generateFull(CampaignDraft $draft)
    {
        try {
            $copyEnrichment = $this->copyService->generateForDraft($draft);
            $creativeEnrichment = $this->creativeService->generateForDraft($draft);

            return redirect()
                ->route('admin.campaign-drafts.show', $draft)
                ->with('success', 'Full enrichment (copy + creative) generated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.campaign-drafts.show', $draft)
                ->with('error', 'Failed to generate full enrichment: ' . $e->getMessage());
        }
    }
}
