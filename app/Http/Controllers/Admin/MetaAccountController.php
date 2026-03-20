<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Meta\SyncMetaCampaignsJob;
use App\Models\MetaAdAccount;
use Illuminate\Http\Request;

class MetaAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = MetaAdAccount::query()
            ->withCount('campaigns')
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.meta-accounts.index', compact('accounts'));
    }

    public function show(Request $request, MetaAdAccount $account)
    {
        $account->load(['campaigns' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        // Get recent insights for this account
        $campaigns = $account->campaigns;
        $campaignIds = $campaigns->pluck('id')->toArray();

        $recentInsights = \App\Models\MetaInsightDaily::whereIn('entity_local_id', $campaignIds)
            ->where('entity_type', 'campaign')
            ->where('insight_date', '>=', now()->subDays(30))
            ->orderBy('insight_date', 'desc')
            ->get();

        // Calculate totals
        $totalSpend = $recentInsights->sum('spend');
        $totalImpressions = $recentInsights->sum('impressions');
        $totalClicks = $recentInsights->sum('clicks');

        return view('admin.meta-accounts.show', compact(
            'account',
            'totalSpend',
            'totalImpressions',
            'totalClicks'
        ));
    }

    public function syncCampaigns(Request $request, MetaAdAccount $account)
    {
        SyncMetaCampaignsJob::dispatch($account->id);

        return redirect()
            ->route('admin.meta-accounts.show', $account)
            ->with('success', 'Campaign sync has been queued.');
    }
}
