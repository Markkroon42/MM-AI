<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApprovalTypeEnum;
use App\Enums\CampaignDraftStatusEnum;
use App\Enums\PublishActionTypeEnum;
use App\Http\Controllers\Controller;
use App\Jobs\Execution\ExecutePublishJob;
use App\Models\AuditLog;
use App\Models\CampaignDraft;
use App\Services\Approval\ApprovalService;
use App\Services\Execution\PublishJobService;
use Illuminate\Http\Request;

class CampaignDraftController extends Controller
{
    public function __construct(
        protected ApprovalService $approvalService,
        protected PublishJobService $publishJobService,
        protected \App\Services\CampaignBuilder\CampaignBuilderViewModel $viewModel
    ) {
        //
    }

    public function index(Request $request)
    {
        $query = CampaignDraft::with('briefing', 'template', 'approver')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('briefing_id')) {
            $query->where('briefing_id', $request->briefing_id);
        }

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        $drafts = $query->paginate(15);

        return view('admin.campaign-drafts.index', compact('drafts'));
    }

    public function show(CampaignDraft $draft)
    {
        $draft->load('briefing', 'template', 'approver', 'approvals', 'publishJobs', 'draftEnrichments');

        $data = $this->viewModel->buildForDraft($draft);

        return view('admin.campaign-drafts.builder', $data);
    }

    public function requestReview(CampaignDraft $draft)
    {
        if ($draft->status !== CampaignDraftStatusEnum::DRAFT->value) {
            return back()->with('error', 'Only drafts can be submitted for review.');
        }

        $draft->update([
            'status' => CampaignDraftStatusEnum::READY_FOR_REVIEW->value,
        ]);

        AuditLog::log(
            'draft_status_changed',
            $draft,
            ['status' => CampaignDraftStatusEnum::DRAFT->value],
            ['status' => CampaignDraftStatusEnum::READY_FOR_REVIEW->value]
        );

        return back()->with('success', 'Draft submitted for review.');
    }

    public function requestApproval(Request $request, CampaignDraft $draft)
    {
        if ($draft->status !== CampaignDraftStatusEnum::READY_FOR_REVIEW->value) {
            return back()->with('error', 'Draft must be ready for review to request approval.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $this->approvalService->requestApproval(
            $draft,
            ApprovalTypeEnum::CAMPAIGN_DRAFT_PUBLISH,
            auth()->user(),
            $draft->draft_payload_json,
            $validated['notes'] ?? null
        );

        return back()->with('success', 'Approval requested successfully.');
    }

    public function publish(CampaignDraft $draft)
    {
        if ($draft->status !== CampaignDraftStatusEnum::APPROVED->value) {
            return back()->with('error', 'Only approved drafts can be published.');
        }

        // Create publish job
        $publishJob = $this->publishJobService->create(
            $draft,
            PublishActionTypeEnum::PUBLISH_CAMPAIGN_DRAFT,
            $draft->draft_payload_json
        );

        // Update draft status
        $draft->update([
            'status' => CampaignDraftStatusEnum::PUBLISHING->value,
        ]);

        // Dispatch job
        ExecutePublishJob::dispatch($publishJob);

        return back()->with('success', 'Draft is being published.');
    }
}
