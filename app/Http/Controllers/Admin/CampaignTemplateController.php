<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignTemplate;
use App\Models\UtmTemplate;
use Illuminate\Http\Request;

class CampaignTemplateController extends Controller
{
    public function __construct()
    {
        //
    }

    public function index()
    {
        $templates = CampaignTemplate::with('utmTemplate')
            ->orderBy('brand')
            ->orderBy('market')
            ->paginate(15);

        return view('admin.campaign-templates.index', compact('templates'));
    }

    public function show(CampaignTemplate $template)
    {
        $template->load('utmTemplate', 'campaignDrafts');

        return view('admin.campaign-templates.show', compact('template'));
    }

    public function create()
    {
        $utmTemplates = UtmTemplate::where('is_active', true)->get();

        return view('admin.campaign-templates.create', compact('utmTemplates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'market' => 'required|string|max:255',
            'objective' => 'required|string|max:255',
            'funnel_stage' => 'required|string|max:255',
            'default_budget' => 'required|numeric|min:0',
            'default_utm_template_id' => 'nullable|exists:utm_templates,id',
            'structure_json' => 'required|json',
            'creative_rules_json' => 'required|json',
            'is_active' => 'boolean',
        ]);

        $validated['structure_json'] = json_decode($validated['structure_json'], true);
        $validated['creative_rules_json'] = json_decode($validated['creative_rules_json'], true);

        $template = CampaignTemplate::create($validated);

        return redirect()
            ->route('admin.campaign-templates.show', $template)
            ->with('success', 'Campaign template created successfully.');
    }

    public function edit(CampaignTemplate $template)
    {
        $utmTemplates = UtmTemplate::where('is_active', true)->get();

        return view('admin.campaign-templates.edit', compact('template', 'utmTemplates'));
    }

    public function update(Request $request, CampaignTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'market' => 'required|string|max:255',
            'objective' => 'required|string|max:255',
            'funnel_stage' => 'required|string|max:255',
            'default_budget' => 'required|numeric|min:0',
            'default_utm_template_id' => 'nullable|exists:utm_templates,id',
            'structure_json' => 'required|json',
            'creative_rules_json' => 'required|json',
            'is_active' => 'boolean',
        ]);

        $validated['structure_json'] = json_decode($validated['structure_json'], true);
        $validated['creative_rules_json'] = json_decode($validated['creative_rules_json'], true);

        $template->update($validated);

        return redirect()
            ->route('admin.campaign-templates.show', $template)
            ->with('success', 'Campaign template updated successfully.');
    }
}
