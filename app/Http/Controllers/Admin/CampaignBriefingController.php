<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignBriefing;
use App\Models\CampaignTemplate;
use App\Services\CampaignDraft\CampaignDraftBuilderService;
use Illuminate\Http\Request;

class CampaignBriefingController extends Controller
{
    public function __construct(
        protected CampaignDraftBuilderService $draftBuilder
    ) {
        //
    }

    public function index(Request $request)
    {
        $query = CampaignBriefing::with('creator')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->filled('market')) {
            $query->where('market', $request->market);
        }

        $briefings = $query->paginate(15);

        return view('admin.campaign-briefings.index', compact('briefings'));
    }

    public function show(CampaignBriefing $briefing)
    {
        $briefing->load('creator', 'campaignDrafts');

        return view('admin.campaign-briefings.show', compact('briefing'));
    }

    public function create()
    {
        return view('admin.campaign-briefings.builder');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'market' => 'required|string|max:255',
            'objective' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
            'target_audience' => 'required|string',
            'landing_page_url' => 'required|url',
            'budget_amount' => 'required|numeric|min:0',
            'campaign_goal' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        $briefing = CampaignBriefing::create($validated);

        return redirect()
            ->route('admin.campaign-briefings.show', $briefing)
            ->with('success', 'Campaign briefing created successfully.');
    }

    public function generateDraft(Request $request, CampaignBriefing $briefing)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:campaign_templates,id',
        ]);

        $template = CampaignTemplate::findOrFail($validated['template_id']);

        $draft = $this->draftBuilder->buildFromBriefing($briefing, $template);

        return redirect()
            ->route('admin.campaign-drafts.show', $draft)
            ->with('success', 'Campaign draft generated successfully.');
    }
}
