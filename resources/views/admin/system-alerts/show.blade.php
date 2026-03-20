@extends('layouts.admin')

@section('title', 'System Alert: ' . $alert->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">{{ $alert->title }}</h1>
        <p class="text-muted mb-0">
            @php
                $severityColors = [
                    'low' => 'secondary',
                    'medium' => 'info',
                    'high' => 'warning',
                    'critical' => 'danger'
                ];
            @endphp
            <span class="badge bg-{{ $severityColors[$alert->severity] ?? 'secondary' }} fs-6">
                {{ ucfirst($alert->severity) }} Severity
            </span>
            <span class="badge bg-secondary fs-6 ms-2">
                {{ ucfirst($alert->alert_type) }}
            </span>
        </p>
    </div>
    <div>
        <a href="{{ route('admin.system-alerts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Alerts
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header {{ $alert->isCritical() ? 'bg-danger text-white' : '' }}">
                <h5 class="mb-0">Alert Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Status:</strong>
                    </div>
                    <div class="col-md-9">
                        @php
                            $statusColors = [
                                'open' => 'danger',
                                'acknowledged' => 'warning',
                                'resolved' => 'success'
                            ];
                        @endphp
                        <span class="badge bg-{{ $statusColors[$alert->status] ?? 'secondary' }} fs-6">
                            {{ ucfirst($alert->status) }}
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Message:</strong>
                    </div>
                    <div class="col-md-9">
                        <div class="alert alert-{{ $alert->isCritical() ? 'danger' : 'info' }} mb-0">
                            {{ $alert->message }}
                        </div>
                    </div>
                </div>

                @if($alert->related_entity_type && $alert->related_entity_id)
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Related Entity:</strong>
                    </div>
                    <div class="col-md-9">
                        <code>{{ $alert->related_entity_type }}</code> #{{ $alert->related_entity_id }}
                    </div>
                </div>
                @endif

                @if($alert->context_json && count($alert->context_json) > 0)
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Context:</strong>
                    </div>
                    <div class="col-md-9">
                        <pre class="bg-light p-3 rounded mb-0"><code>{{ json_encode($alert->context_json, JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Created At:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $alert->created_at->format('M d, Y H:i:s') }}
                        <span class="text-muted">({{ $alert->created_at->diffForHumans() }})</span>
                    </div>
                </div>

                @if($alert->acknowledged_at)
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Acknowledged:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $alert->acknowledged_at->format('M d, Y H:i:s') }}
                        @if($alert->acknowledgedBy)
                            <br><small class="text-muted">by {{ $alert->acknowledgedBy->name }}</small>
                        @endif
                    </div>
                </div>
                @endif

                @if($alert->resolved_at)
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Resolved:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $alert->resolved_at->format('M d, Y H:i:s') }}
                        @if($alert->resolvedBy)
                            <br><small class="text-muted">by {{ $alert->resolvedBy->name }}</small>
                        @endif
                    </div>
                </div>
                @endif

                @if($alert->resolution_notes)
                <div class="row mb-0">
                    <div class="col-md-3">
                        <strong>Resolution Notes:</strong>
                    </div>
                    <div class="col-md-9">
                        <div class="alert alert-success mb-0">
                            {{ $alert->resolution_notes }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($alert->isResolved())
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill me-2"></i>
            This alert has been resolved and is now closed.
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        @if(!$alert->isResolved())
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                @if($alert->isOpen())
                    <form method="POST" action="{{ route('admin.system-alerts.acknowledge', $alert) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-check-circle me-1"></i> Acknowledge Alert
                        </button>
                    </form>
                @endif

                @if(!$alert->isResolved())
                    <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#resolveModal">
                        <i class="bi bi-check-circle-fill me-1"></i> Resolve Alert
                    </button>
                @endif
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Alert Timeline</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="mb-3">
                        <i class="bi bi-circle-fill text-danger me-2"></i>
                        <strong>Created</strong>
                        <br><small class="text-muted ms-4">{{ $alert->created_at->format('M d, Y H:i:s') }}</small>
                    </div>

                    @if($alert->acknowledged_at)
                    <div class="mb-3">
                        <i class="bi bi-circle-fill text-warning me-2"></i>
                        <strong>Acknowledged</strong>
                        <br><small class="text-muted ms-4">{{ $alert->acknowledged_at->format('M d, Y H:i:s') }}</small>
                        @if($alert->acknowledgedBy)
                            <br><small class="text-muted ms-4">by {{ $alert->acknowledgedBy->name }}</small>
                        @endif
                    </div>
                    @endif

                    @if($alert->resolved_at)
                    <div class="mb-0">
                        <i class="bi bi-circle-fill text-success me-2"></i>
                        <strong>Resolved</strong>
                        <br><small class="text-muted ms-4">{{ $alert->resolved_at->format('M d, Y H:i:s') }}</small>
                        @if($alert->resolvedBy)
                            <br><small class="text-muted ms-4">by {{ $alert->resolvedBy->name }}</small>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Resolve Modal --}}
@if(!$alert->isResolved())
<div class="modal fade" id="resolveModal" tabindex="-1" aria-labelledby="resolveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.system-alerts.resolve', $alert) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="resolveModalLabel">Resolve Alert</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="resolution_notes" class="form-label">Resolution Notes</label>
                        <textarea name="resolution_notes" id="resolution_notes" rows="4" class="form-control"
                                  placeholder="Describe how this issue was resolved..."></textarea>
                        <small class="form-text text-muted">Optional: Provide details about the resolution</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle-fill me-1"></i> Resolve Alert
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
