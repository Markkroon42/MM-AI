<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignRecommendation;
use App\Models\MetaCampaign;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    /**
     * Display a listing of recommendations.
     */
    public function index(Request $request)
    {
        $query = CampaignRecommendation::with([
            'campaign',
            'adSet',
            'ad',
            'agentRun',
            'reviewedBy'
        ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('recommendation_type', $request->type);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by source agent
        if ($request->filled('source_agent')) {
            $query->where('source_agent', $request->source_agent);
        }

        // Filter by campaign
        if ($request->filled('campaign_id')) {
            $query->where('meta_campaign_id', $request->campaign_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('explanation', 'like', "%{$search}%")
                  ->orWhereHas('campaign', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $recommendations = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get filter options
        $campaigns = MetaCampaign::orderBy('name')->get(['id', 'name']);
        $statuses = ['new', 'reviewing', 'approved', 'rejected', 'executed'];
        $severities = ['low', 'medium', 'high', 'critical'];
        $types = [
            'low_ctr', 'high_cpc', 'low_roas', 'high_frequency',
            'no_ad_sets', 'no_ads', 'missing_utm', 'naming_violation',
            'duplicate_structure', 'inactive_but_spending', 'budget_underutilized',
            'spend_without_purchases', 'creative_fatigue', 'scale_winner', 'pause_loser'
        ];
        $sourceAgents = ['structure_agent', 'performance_agent'];

        return view('admin.recommendations.index', compact(
            'recommendations',
            'campaigns',
            'statuses',
            'severities',
            'types',
            'sourceAgents'
        ));
    }

    /**
     * Display the specified recommendation.
     */
    public function show(CampaignRecommendation $recommendation)
    {
        $recommendation->load([
            'campaign.metaAdAccount',
            'adSet',
            'ad',
            'agentRun',
            'reviewedBy'
        ]);

        return view('admin.recommendations.show', compact('recommendation'));
    }
}
