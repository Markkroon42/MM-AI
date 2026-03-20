<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetaCampaign;
use Illuminate\Http\Request;

class MetaCampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = MetaCampaign::with('metaAdAccount');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by account
        if ($request->filled('account_id')) {
            $query->where('meta_ad_account_id', $request->account_id);
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $campaigns = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        // Get accounts for filter dropdown
        $accounts = \App\Models\MetaAdAccount::orderBy('name')->get();

        return view('admin.campaigns.index', compact('campaigns', 'accounts'));
    }

    public function show(Request $request, MetaCampaign $campaign)
    {
        $campaign->load([
            'metaAdAccount',
            'adSets.ads',
        ]);

        // Get insights for this campaign (last 30 days)
        $insights = $campaign->insights()
            ->where('insight_date', '>=', now()->subDays(30))
            ->orderBy('insight_date', 'desc')
            ->get();

        // Calculate totals
        $totalSpend = $insights->sum('spend');
        $totalImpressions = $insights->sum('impressions');
        $totalClicks = $insights->sum('clicks');
        $totalPurchases = $insights->sum('purchases');
        $totalRevenue = $insights->sum('purchase_value');

        // Calculate averages
        $avgCtr = $insights->avg('ctr');
        $avgCpc = $insights->avg('cpc');
        $avgCpm = $insights->avg('cpm');
        $avgRoas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;

        return view('admin.campaigns.show', compact(
            'campaign',
            'insights',
            'totalSpend',
            'totalImpressions',
            'totalClicks',
            'totalPurchases',
            'totalRevenue',
            'avgCtr',
            'avgCpc',
            'avgCpm',
            'avgRoas'
        ));
    }
}
