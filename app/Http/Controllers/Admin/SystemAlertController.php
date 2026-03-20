<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemAlert;
use App\Services\Reporting\SystemAlertService;
use Illuminate\Http\Request;

class SystemAlertController extends Controller
{
    public function __construct(
        protected SystemAlertService $alertService
    ) {}

    public function index(Request $request)
    {
        $query = SystemAlert::query();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            // Default to open alerts
            $query->where('status', 'open');
        }

        // Filter by severity
        if ($request->has('severity') && $request->severity !== 'all') {
            $query->where('severity', $request->severity);
        }

        $alerts = $query->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.system-alerts.index', compact('alerts'));
    }

    public function show(SystemAlert $alert)
    {
        $alert->load(['acknowledgedBy', 'resolvedBy']);

        return view('admin.system-alerts.show', compact('alert'));
    }

    public function acknowledge(SystemAlert $alert)
    {
        $this->alertService->acknowledge($alert, auth()->user());

        return redirect()->route('admin.system-alerts.show', $alert)
            ->with('success', 'Alert acknowledged successfully.');
    }

    public function resolve(Request $request, SystemAlert $alert)
    {
        $validated = $request->validate([
            'resolution_notes' => 'nullable|string',
        ]);

        $this->alertService->resolve($alert, auth()->user(), $validated['resolution_notes'] ?? null);

        return redirect()->route('admin.system-alerts.index')
            ->with('success', 'Alert resolved successfully.');
    }
}
