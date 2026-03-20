@extends('layouts.admin')

@section('title', 'Recommendation #' . $recommendation->id)

@section('content')
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.recommendations.index') }}">Recommendations</a></li>
            <li class="breadcrumb-item active">{{ $recommendation->title }}</li>
        </ol>
    </nav>
    <h1 class="h2">{{ $recommendation->title }}</h1>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <!-- Main Details -->
    <div class="col-md-8 mb-4">
        <div class="card mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recommendation Details</h5>
                <div>
                    @php
                        $severityClass = match($recommendation->severity) {
                            'critical' => 'bg-dark',
                            'high' => 'bg-danger',
                            'medium' => 'bg-warning',
                            'low' => 'bg-info',
                            default => 'bg-secondary'
                        };
                        $statusClass = match($recommendation->status) {
                            'new' => 'bg-primary',
                            'reviewing' => 'bg-warning',
                            'approved' => 'bg-success',
                            'rejected' => 'bg-danger',
                            'executed' => 'bg-info',
                            default => 'bg-secondary'
                        };
                    @endphp
                    <span class="badge {{ $severityClass }} me-2">{{ ucfirst($recommendation->severity) }}</span>
                    <span class="badge {{ $statusClass }}">{{ ucfirst($recommendation->status) }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="text-muted text-uppercase small mb-2">Type</h6>
                    <p class="mb-0">
                        <span class="badge bg-secondary">
                            {{ str_replace('_', ' ', ucwords($recommendation->recommendation_type, '_')) }}
                        </span>
                    </p>
                </div>

                <div class="mb-4">
                    <h6 class="text-muted text-uppercase small mb-2">Explanation</h6>
                    <div class="alert alert-light">
                        {{ $recommendation->explanation }}
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="text-muted text-uppercase small mb-2">Proposed Action</h6>
                    <div class="alert alert-info">
                        <i class="bi bi-lightbulb me-2"></i>
                        {{ $recommendation->proposed_action }}
                    </div>
                </div>

                @if($recommendation->action_payload_json)
                    <div class="mb-3">
                        <h6 class="text-muted text-uppercase small mb-2">Action Payload</h6>
                        <pre class="bg-light p-3 rounded"><code>{{ json_encode($recommendation->action_payload_json, JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                @endif
            </div>
        </div>

        <!-- Review Section -->
        @if($recommendation->reviewed_at)
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Review Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tbody>
                            <tr>
                                <td class="fw-semibold">Reviewed By</td>
                                <td>{{ $recommendation->reviewedBy?->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-semibold">Reviewed At</td>
                                <td>{{ $recommendation->reviewed_at->format('M j, Y g:i A') }}</td>
                            </tr>
                            @if($recommendation->review_notes)
                                <tr>
                                    <td class="fw-semibold">Review Notes</td>
                                    <td>{{ $recommendation->review_notes }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <!-- Review Actions -->
            @if(in_array($recommendation->status, ['new', 'reviewing']))
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Review Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-2">
                            @if($recommendation->status === 'new')
                                <form method="POST" action="{{ route('admin.recommendations.reviewing', $recommendation) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-eye me-1"></i> Mark as Reviewing
                                    </button>
                                </form>
                            @endif

                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                                <i class="bi bi-check-circle me-1"></i> Approve
                            </button>

                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                <i class="bi bi-x-circle me-1"></i> Reject
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- Sidebar -->
    <div class="col-md-4 mb-4">
        <!-- Related Entity -->
        <div class="card mb-3">
            <div class="card-header bg-white">
                <h5 class="mb-0">Related Entity</h5>
            </div>
            <div class="card-body">
                @if($recommendation->campaign)
                    <div class="mb-3">
                        <h6 class="text-muted small">Campaign</h6>
                        <a href="{{ route('admin.campaigns.show', $recommendation->campaign) }}">
                            {{ $recommendation->campaign->name }}
                        </a>
                    </div>
                @endif
                @if($recommendation->adSet)
                    <div class="mb-3">
                        <h6 class="text-muted small">Ad Set</h6>
                        <p class="mb-0">{{ $recommendation->adSet->name }}</p>
                    </div>
                @endif
                @if($recommendation->ad)
                    <div class="mb-3">
                        <h6 class="text-muted small">Ad</h6>
                        <p class="mb-0">{{ $recommendation->ad->name }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Metadata -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Metadata</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="fw-semibold">Source Agent</td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ str_replace('_', ' ', ucwords($recommendation->source_agent, '_')) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Confidence</td>
                            <td>{{ $recommendation->confidence_score }}%</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Created</td>
                            <td>{{ $recommendation->created_at->format('M j, Y g:i A') }}</td>
                        </tr>
                        @if($recommendation->agentRun)
                            <tr>
                                <td class="fw-semibold">Agent Run</td>
                                <td>#{{ $recommendation->agentRun->id }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.recommendations.approve', $recommendation) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Recommendation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="approve_notes" class="form-label">Review Notes (Optional)</label>
                        <textarea name="review_notes" id="approve_notes" class="form-control" rows="3"
                                  placeholder="Add any notes about this approval..."></textarea>
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
            <form method="POST" action="{{ route('admin.recommendations.reject', $recommendation) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Recommendation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_notes" class="form-label">Review Notes (Required)</label>
                        <textarea name="review_notes" id="reject_notes" class="form-control" rows="3"
                                  placeholder="Explain why you're rejecting this recommendation..." required></textarea>
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
