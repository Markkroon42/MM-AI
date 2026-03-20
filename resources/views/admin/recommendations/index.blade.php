@extends('layouts.admin')

@section('title', 'Recommendations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">AI Recommendations</h1>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.recommendations.index') }}" class="row g-3">
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="type" class="form-label">Type</label>
                <select name="type" id="type" class="form-select">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                            {{ str_replace('_', ' ', ucwords($type, '_')) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="severity" class="form-label">Severity</label>
                <select name="severity" id="severity" class="form-select">
                    <option value="">All Severities</option>
                    @foreach($severities as $severity)
                        <option value="{{ $severity }}" {{ request('severity') === $severity ? 'selected' : '' }}>
                            {{ ucfirst($severity) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="source_agent" class="form-label">Source</label>
                <select name="source_agent" id="source_agent" class="form-select">
                    <option value="">All Sources</option>
                    @foreach($sourceAgents as $agent)
                        <option value="{{ $agent }}" {{ request('source_agent') === $agent ? 'selected' : '' }}>
                            {{ str_replace('_', ' ', ucwords($agent, '_')) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" name="search" id="search" class="form-control"
                       placeholder="Search title or explanation..." value="{{ request('search') }}">
            </div>

            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Recommendations Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Campaign</th>
                        <th>Title</th>
                        <th>Source</th>
                        <th>Confidence</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recommendations as $recommendation)
                        <tr>
                            <td>#{{ $recommendation->id }}</td>
                            <td>
                                @php
                                    $typeClass = match($recommendation->recommendation_type) {
                                        'scale_winner' => 'bg-success',
                                        'pause_loser', 'spend_without_purchases', 'inactive_but_spending' => 'bg-danger',
                                        'low_roas', 'high_cpc', 'low_ctr' => 'bg-warning',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $typeClass }}">
                                    {{ str_replace('_', ' ', ucwords($recommendation->recommendation_type, '_')) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $severityClass = match($recommendation->severity) {
                                        'critical' => 'bg-dark',
                                        'high' => 'bg-danger',
                                        'medium' => 'bg-warning',
                                        'low' => 'bg-info',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $severityClass }}">
                                    {{ ucfirst($recommendation->severity) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $statusClass = match($recommendation->status) {
                                        'new' => 'bg-primary',
                                        'reviewing' => 'bg-warning',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        'executed' => 'bg-info',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($recommendation->status) }}
                                </span>
                            </td>
                            <td>
                                @if($recommendation->campaign)
                                    <a href="{{ route('admin.campaigns.show', $recommendation->campaign) }}">
                                        {{ Str::limit($recommendation->campaign->name, 30) }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ Str::limit($recommendation->title, 50) }}</td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ str_replace('_', ' ', ucwords($recommendation->source_agent, '_')) }}
                                </span>
                            </td>
                            <td>{{ $recommendation->confidence_score }}%</td>
                            <td>{{ $recommendation->created_at->format('M j, g:i A') }}</td>
                            <td>
                                <a href="{{ route('admin.recommendations.show', $recommendation) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                No recommendations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($recommendations->hasPages())
        <div class="card-footer">
            {{ $recommendations->links() }}
        </div>
    @endif
</div>
@endsection
