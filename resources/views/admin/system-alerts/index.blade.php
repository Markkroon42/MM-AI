@extends('layouts.admin')

@section('title', 'System Alerts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">System Alerts</h1>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Filter Options --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.system-alerts.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="open" {{ request('status', 'open') == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="acknowledged" {{ request('status') == 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="severity" class="form-label">Severity</label>
                <select name="severity" id="severity" class="form-select">
                    <option value="all" {{ request('severity') == 'all' ? 'selected' : '' }}>All Severities</option>
                    <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                    <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>Low</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('admin.system-alerts.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Severity</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr class="{{ $alert->isCritical() ? 'table-danger' : '' }}">
                            <td>
                                @php
                                    $severityColors = [
                                        'low' => 'secondary',
                                        'medium' => 'info',
                                        'high' => 'warning',
                                        'critical' => 'danger'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $severityColors[$alert->severity] ?? 'secondary' }}">
                                    {{ ucfirst($alert->severity) }}
                                </span>
                            </td>
                            <td>
                                <code>{{ $alert->alert_type }}</code>
                            </td>
                            <td>
                                <strong>{{ $alert->title }}</strong>
                                <br><small class="text-muted">{{ Str::limit($alert->message, 60) }}</small>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'open' => 'danger',
                                        'acknowledged' => 'warning',
                                        'resolved' => 'success'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$alert->status] ?? 'secondary' }}">
                                    {{ ucfirst($alert->status) }}
                                </span>
                            </td>
                            <td>
                                {{ $alert->created_at->diffForHumans() }}
                                <br><small class="text-muted">{{ $alert->created_at->format('M d, H:i') }}</small>
                            </td>
                            <td>
                                <a href="{{ route('admin.system-alerts.show', $alert) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                @if(request('status') || request('severity'))
                                    No alerts found matching your filters.
                                @else
                                    No system alerts found. Your system is healthy!
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $alerts->appends(request()->query())->links() }}
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="card border-info">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>About System Alerts</h5>
            <p class="card-text mb-2">
                System alerts notify you of important events, errors, and conditions that require attention.
            </p>
            <ul class="mb-0">
                <li><strong>Open:</strong> New alert requiring attention</li>
                <li><strong>Acknowledged:</strong> Alert has been seen but not yet resolved</li>
                <li><strong>Resolved:</strong> Issue has been addressed and closed</li>
                <li><strong>Severity:</strong> Critical alerts require immediate action</li>
            </ul>
        </div>
    </div>
</div>
@endsection
