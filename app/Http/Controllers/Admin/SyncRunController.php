<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncRun;
use Illuminate\Http\Request;

class SyncRunController extends Controller
{
    public function index(Request $request)
    {
        $query = SyncRun::query();

        // Filter by provider
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by sync type
        if ($request->filled('sync_type')) {
            $query->where('sync_type', $request->sync_type);
        }

        $syncRuns = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.sync-runs.index', compact('syncRuns'));
    }
}
