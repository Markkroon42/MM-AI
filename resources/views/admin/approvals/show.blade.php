@extends('layouts.admin')

@section('title', 'Approval Details')

@section('content')
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.approvals.index') }}">Approvals</a></li>
        <li class="breadcrumb-item active">Approval #{{ $approval->id }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Approval Details</h1>
    <span class="badge bg-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'warning') }}">
        {{ ucfirst($approval->status) }}
    </span>
</div>

<!-- Actions -->
@if($approval->status === 'pending')
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                Approve
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                Reject
            </button>
        </div>
    </div>
</div>
@endif

<!-- Approval Details -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Approval Information</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Type:</strong> {{ ucwords(str_replace('_', ' ', $approval->approval_type)) }}
            </div>
            <div class="col-md-6">
                <strong>Status:</strong> {{ ucfirst($approval->status) }}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Approvable:</strong> {{ class_basename($approval->approvable_type) }} #{{ $approval->approvable_id }}
            </div>
            <div class="col-md-6">
                <strong>Requested By:</strong> {{ $approval->requester?->name ?? 'System' }}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Requested At:</strong> {{ $approval->requested_at?->format('Y-m-d H:i:s') ?? $approval->created_at->format('Y-m-d H:i:s') }}
            </div>
            @if($approval->decided_at)
                <div class="col-md-6">
                    <strong>Decided At:</strong> {{ $approval->decided_at->format('Y-m-d H:i:s') }}
                </div>
            @endif
        </div>
        @if($approval->approver)
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Approved By:</strong> {{ $approval->approver->name }}
                </div>
            </div>
        @endif
        @if($approval->rejector)
            <div class="row mb-3">
                <div class="col-md-6">
                    <strong>Rejected By:</strong> {{ $approval->rejector->name }}
                </div>
            </div>
        @endif
        @if($approval->notes)
            <div class="mb-3">
                <strong>Notes:</strong>
                <p class="mt-2">{{ $approval->notes }}</p>
            </div>
        @endif
    </div>
</div>

<!-- Payload -->
@if($approval->payload_json)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Approval Payload</h5>
    </div>
    <div class="card-body">
        <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($approval->payload_json, JSON_PRETTY_PRINT) }}</code></pre>
    </div>
</div>
@endif

<!-- Approvable Details -->
@if($approval->approvable)
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Related {{ class_basename($approval->approvable_type) }}</h5>
    </div>
    <div class="card-body">
        @if($approval->approvable_type === 'App\\Models\\CampaignDraft')
            <p><strong>Name:</strong> {{ $approval->approvable->generated_name }}</p>
            <p><strong>Status:</strong> {{ $approval->approvable->status }}</p>
            <a href="{{ route('admin.campaign-drafts.show', $approval->approvable) }}" class="btn btn-primary">View Draft</a>
        @else
            <p>{{ class_basename($approval->approvable_type) }} #{{ $approval->approvable_id }}</p>
        @endif
    </div>
</div>
@endif

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.approvals.approve', $approval) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approve_notes" class="form-label">Notes (Optional)</label>
                        <textarea name="notes" id="approve_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.approvals.reject', $approval) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_notes" class="form-label">Notes (Required)</label>
                        <textarea name="notes" id="reject_notes" class="form-control" rows="3" required></textarea>
                        <div class="form-text">Please provide a reason for rejection.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
