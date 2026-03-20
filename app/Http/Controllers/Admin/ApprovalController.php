<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Services\Approval\ApprovalService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService
    ) {
        //
    }

    public function index(Request $request)
    {
        $query = Approval::with('approvable', 'requester', 'approver', 'rejector')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('approval_type')) {
            $query->where('approval_type', $request->approval_type);
        }

        if ($request->filled('approvable_type')) {
            $query->where('approvable_type', $request->approvable_type);
        }

        $approvals = $query->paginate(15);

        return view('admin.approvals.index', compact('approvals'));
    }

    public function show(Approval $approval)
    {
        $approval->load('approvable', 'requester', 'approver', 'rejector');

        return view('admin.approvals.show', compact('approval'));
    }

    public function approve(Request $request, Approval $approval)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            $this->approvalService->approve($approval, auth()->user(), $validated['notes'] ?? null);

            // Update approvable status if it's a draft
            if ($approval->approvable_type === 'App\\Models\\CampaignDraft') {
                $approval->approvable->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            }

            return back()->with('success', 'Approval granted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Approval $approval)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
        ]);

        try {
            $this->approvalService->reject($approval, auth()->user(), $validated['notes']);

            // Update approvable status if it's a draft
            if ($approval->approvable_type === 'App\\Models\\CampaignDraft') {
                $approval->approvable->update([
                    'status' => 'rejected',
                    'review_notes' => $validated['notes'],
                ]);
            }

            return back()->with('success', 'Approval rejected.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
