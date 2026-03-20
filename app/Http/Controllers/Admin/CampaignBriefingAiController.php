<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignBriefing;
use App\Services\AI\CampaignStrategyAssistantService;
use App\Services\AI\CopyAgentService;
use App\Services\AI\CreativeSuggestionAgentService;
use Illuminate\Http\Request;

class CampaignBriefingAiController extends Controller
{
    protected CampaignStrategyAssistantService $strategyService;
    protected CopyAgentService $copyService;
    protected CreativeSuggestionAgentService $creativeService;

    public function __construct(
        CampaignStrategyAssistantService $strategyService,
        CopyAgentService $copyService,
        CreativeSuggestionAgentService $creativeService
    ) {
        $this->strategyService = $strategyService;
        $this->copyService = $copyService;
        $this->creativeService = $creativeService;
    }

    public function generateStrategy(CampaignBriefing $briefing)
    {
        try {
            $strategyNote = $this->strategyService->generateForBriefing($briefing);

            return redirect()
                ->route('admin.campaign-briefings.show', $briefing)
                ->with('success', 'Strategy generated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.campaign-briefings.show', $briefing)
                ->with('error', 'Failed to generate strategy: ' . $e->getMessage());
        }
    }

    public function generateCopy(CampaignBriefing $briefing)
    {
        try {
            $enrichment = $this->copyService->generateForBriefing($briefing);

            return redirect()
                ->route('admin.campaign-briefings.show', $briefing)
                ->with('success', 'Copy variants generated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.campaign-briefings.show', $briefing)
                ->with('error', 'Failed to generate copy: ' . $e->getMessage());
        }
    }

    public function generateCreative(CampaignBriefing $briefing)
    {
        try {
            $enrichment = $this->creativeService->generateForBriefing($briefing);

            return redirect()
                ->route('admin.campaign-briefings.show', $briefing)
                ->with('success', 'Creative suggestions generated successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.campaign-briefings.show', $briefing)
                ->with('error', 'Failed to generate creative suggestions: ' . $e->getMessage());
        }
    }
}
