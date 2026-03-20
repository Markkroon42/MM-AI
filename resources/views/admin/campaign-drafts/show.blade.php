@extends('layouts.admin')

@section('title', 'Campaign Draft Details')

@section('content')
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.campaign-drafts.index') }}">Campaign Drafts</a></li>
        <li class="breadcrumb-item active">{{ $draft->generated_name }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">{{ $draft->generated_name }}</h1>
    <span class="badge {{ $draft->status === 'published' ? 'bg-primary' : ($draft->status === 'approved' ? 'bg-success' : 'bg-secondary') }}">
        {{ ucwords(str_replace('_', ' ', $draft->status)) }}
    </span>
</div>

<!-- Actions -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex gap-2">
            @if($draft->status === 'draft')
                <form method="POST" action="{{ route('admin.campaign-drafts.request-review', $draft) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Request Review</button>
                </form>
            @endif

            @if($draft->status === 'ready_for_review')
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#requestApprovalModal">
                    Request Approval
                </button>
            @endif

            @if($draft->status === 'approved')
                <form method="POST" action="{{ route('admin.campaign-drafts.publish', $draft) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Publish Campaign</button>
                </form>
            @endif
        </div>
    </div>
</div>

<!-- Draft Details -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Draft Information</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Briefing:</strong> {{ $draft->briefing?->brand ?? 'N/A' }} - {{ $draft->briefing?->market ?? 'N/A' }}
            </div>
            <div class="col-md-6">
                <strong>Template:</strong> {{ $draft->template?->name ?? 'N/A' }}
            </div>
        </div>
        @if($draft->approved_by)
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Approved By:</strong> {{ $draft->approver->name }}
                </div>
                <div class="col-md-6">
                    <strong>Approved At:</strong> {{ $draft->approved_at->format('Y-m-d H:i') }}
                </div>
            </div>
        @endif
        @if($draft->review_notes)
            <div class="mb-3">
                <strong>Review Notes:</strong>
                <p class="mt-2">{{ $draft->review_notes }}</p>
            </div>
        @endif
    </div>
</div>

<!-- AI Generation Actions -->
@can('generate_ai_copy')
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">AI Content Generation</h5>
    </div>
    <div class="card-body">
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('admin.campaign-drafts.ai.generate-copy', $draft) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-magic"></i> Generate Copy Variants
                </button>
            </form>

            @can('generate_ai_creatives')
            <form method="POST" action="{{ route('admin.campaign-drafts.ai.generate-creative', $draft) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-image"></i> Generate Creative Ideas
                </button>
            </form>
            @endcan

            <form method="POST" action="{{ route('admin.campaign-drafts.ai.generate-full', $draft) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-stars"></i> Generate Full AI Package
                </button>
            </form>
        </div>
    </div>
</div>
@endcan

<!-- AI Enrichments -->
@if($draft->enrichments->count() > 0)
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">AI-Generated Enrichments</h5>
        <span class="badge bg-info">{{ $draft->enrichments->count() }} enrichments</span>
    </div>
    <div class="card-body">
        <div class="accordion" id="enrichmentsAccordion">
            @foreach($draft->enrichments as $index => $enrichment)
                <div class="accordion-item mb-3 border">
                    <h2 class="accordion-header" id="heading{{ $enrichment->id }}">
                        <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $enrichment->id }}">
                            <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                                <div>
                                    <strong>{{ ucwords(str_replace('_', ' ', $enrichment->enrichment_type)) }}</strong>
                                    <span class="badge ms-2 bg-{{
                                        $enrichment->status === 'applied' ? 'success' :
                                        ($enrichment->status === 'approved' ? 'primary' :
                                        ($enrichment->status === 'rejected' ? 'danger' : 'secondary'))
                                    }}">
                                        {{ ucfirst($enrichment->status) }}
                                    </span>
                                </div>
                                <small class="text-muted">{{ $enrichment->created_at->diffForHumans() }}</small>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse{{ $enrichment->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" data-bs-parent="#enrichmentsAccordion">
                        <div class="accordion-body">
                            <!-- Enrichment Metadata -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <small class="text-muted">Created by:</small>
                                    <strong>{{ $enrichment->creator?->name ?? 'System' }}</strong>
                                </div>
                                @if($enrichment->aiUsageLog)
                                <div class="col-md-6">
                                    <small class="text-muted">AI Model:</small>
                                    <strong>{{ $enrichment->aiUsageLog->model }}</strong>
                                    <a href="{{ route('admin.ai-usage-logs.show', $enrichment->aiUsageLog) }}" class="btn btn-sm btn-outline-secondary ms-2" target="_blank">
                                        View Log
                                    </a>
                                </div>
                                @endif
                            </div>

                            <!-- Enrichment Payload -->
                            <div class="mb-3">
                                <strong class="d-block mb-2">Generated Content:</strong>
                                <div class="bg-light p-3 rounded">
                                    @if($enrichment->enrichment_type === 'copy')
                                        @php $payload = $enrichment->payload_json; @endphp
                                        @if(!empty($payload['primary_texts']))
                                            <div class="mb-3">
                                                <strong>Primary Texts:</strong>
                                                <ul class="mb-0 mt-1">
                                                    @foreach($payload['primary_texts'] as $text)
                                                        <li>{{ $text }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if(!empty($payload['headlines']))
                                            <div class="mb-3">
                                                <strong>Headlines:</strong>
                                                <ul class="mb-0 mt-1">
                                                    @foreach($payload['headlines'] as $headline)
                                                        <li>{{ $headline }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if(!empty($payload['descriptions']))
                                            <div class="mb-3">
                                                <strong>Descriptions:</strong>
                                                <ul class="mb-0 mt-1">
                                                    @foreach($payload['descriptions'] as $desc)
                                                        <li>{{ $desc }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if(!empty($payload['call_to_actions']))
                                            <div class="mb-3">
                                                <strong>CTAs:</strong>
                                                <ul class="mb-0 mt-1">
                                                    @foreach($payload['call_to_actions'] as $cta)
                                                        <li>{{ $cta }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    @elseif($enrichment->enrichment_type === 'creative')
                                        @php $payload = $enrichment->payload_json; @endphp
                                        @if(!empty($payload['static_visual_ideas']))
                                            <div class="mb-3">
                                                <strong>Static Visual Ideas:</strong>
                                                <ul class="mb-0 mt-1">
                                                    @foreach($payload['static_visual_ideas'] as $idea)
                                                        <li>{{ $idea }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if(!empty($payload['video_concepts']))
                                            <div class="mb-3">
                                                <strong>Video Concepts:</strong>
                                                <ul class="mb-0 mt-1">
                                                    @foreach($payload['video_concepts'] as $concept)
                                                        <li>{{ $concept }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if(!empty($payload['ugc_angles']))
                                            <div class="mb-3">
                                                <strong>UGC Angles:</strong>
                                                <ul class="mb-0 mt-1">
                                                    @foreach($payload['ugc_angles'] as $angle)
                                                        <li>{{ $angle }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    @else
                                        <pre class="mb-0" style="max-height: 300px; overflow-y: auto;"><code>{{ json_encode($enrichment->payload_json, JSON_PRETTY_PRINT) }}</code></pre>
                                    @endif
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            @if($enrichment->status === 'draft')
                                @can('review_draft_enrichments')
                                <div class="d-flex gap-2">
                                    <form method="POST" action="{{ route('admin.draft-enrichments.approve', $enrichment) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bi bi-check-circle"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.draft-enrichments.reject', $enrichment) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </button>
                                    </form>
                                </div>
                                @endcan
                            @elseif($enrichment->status === 'approved')
                                @can('apply_draft_enrichments')
                                <form method="POST" action="{{ route('admin.draft-enrichments.apply', $enrichment) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-arrow-down-circle"></i> Apply to Draft
                                    </button>
                                </form>
                                @endcan
                            @elseif($enrichment->status === 'applied')
                                <div class="alert alert-success mb-0">
                                    <i class="bi bi-check-circle-fill"></i> This enrichment has been applied to the draft.
                                    @if($enrichment->applied_by)
                                        Applied by {{ $enrichment->applier->name }} at {{ $enrichment->applied_at->format('Y-m-d H:i') }}.
                                    @endif
                                </div>
                            @elseif($enrichment->status === 'rejected')
                                <div class="alert alert-danger mb-0">
                                    <i class="bi bi-x-circle-fill"></i> This enrichment was rejected.
                                    @if($enrichment->rejected_by)
                                        Rejected by {{ $enrichment->rejector->name }} at {{ $enrichment->rejected_at->format('Y-m-d H:i') }}.
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- Draft Payload -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Campaign Structure</h5>
    </div>
    <div class="card-body">
        <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($draft->draft_payload_json, JSON_PRETTY_PRINT) }}</code></pre>
    </div>
</div>

<!-- Approvals -->
@if($draft->approvals->count() > 0)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Approvals</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Decided By</th>
                        <th>Requested At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($draft->approvals as $approval)
                        <tr>
                            <td>{{ ucwords(str_replace('_', ' ', $approval->approval_type)) }}</td>
                            <td><span class="badge bg-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'warning') }}">{{ $approval->status }}</span></td>
                            <td>{{ $approval->requester?->name ?? 'N/A' }}</td>
                            <td>{{ $approval->approver?->name ?? $approval->rejector?->name ?? '-' }}</td>
                            <td>{{ $approval->requested_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.approvals.show', $approval) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Publish Jobs -->
@if($draft->publishJobs->count() > 0)
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Publish Jobs</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Action Type</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Executed At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($draft->publishJobs as $job)
                        <tr>
                            <td>{{ ucwords(str_replace('_', ' ', $job->action_type)) }}</td>
                            <td><span class="badge bg-{{ $job->status === 'success' ? 'success' : ($job->status === 'failed' ? 'danger' : 'warning') }}">{{ $job->status }}</span></td>
                            <td>{{ $job->attempts }}</td>
                            <td>{{ $job->executed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.publish-jobs.show', $job) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Request Approval Modal -->
<div class="modal fade" id="requestApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.campaign-drafts.request-approval', $draft) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Request Approval</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Request Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
