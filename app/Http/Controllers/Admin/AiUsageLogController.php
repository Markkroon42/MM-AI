<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use Illuminate\Http\Request;

class AiUsageLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AiUsageLog::with('promptConfig');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('agent_name')) {
            $query->where('agent_name', $request->agent_name);
        }

        if ($request->filled('date_from')) {
            $query->where('started_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('started_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('started_at', 'desc')->paginate(20);

        // Get unique agent names for filter
        $agentNames = AiUsageLog::distinct()->pluck('agent_name');

        return view('admin.ai-usage-logs.index', compact('logs', 'agentNames'));
    }

    public function show(AiUsageLog $log)
    {
        $log->load('promptConfig', 'source', 'target');

        return view('admin.ai-usage-logs.show', compact('log'));
    }
}
