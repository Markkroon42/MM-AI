@extends('layouts.admin')

@section('title', 'Sync Runs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Sync Runs</h1>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.sync-runs.index') }}" class="row g-3">
            <div class="col-md-3">
                <select name="provider" class="form-select">
                    <option value="">All Providers</option>
                    <option value="meta" {{ request('provider') == 'meta' ? 'selected' : '' }}>Meta</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="running" {{ request('status') == 'running' ? 'selected' : '' }}>Running</option>
                    <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sync_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="ad_accounts" {{ request('sync_type') == 'ad_accounts' ? 'selected' : '' }}>Ad Accounts</option>
                    <option value="campaigns" {{ request('sync_type') == 'campaigns' ? 'selected' : '' }}>Campaigns</option>
                    <option value="campaign_insights" {{ request('sync_type') == 'campaign_insights' ? 'selected' : '' }}>Campaign Insights</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.sync-runs.index') }}" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Provider</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Records</th>
                        <th>Started</th>
                        <th>Finished</th>
                        <th>Duration</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($syncRuns as $syncRun)
                        <tr>
                            <td>#{{ $syncRun->id }}</td>
                            <td><span class="badge bg-secondary">{{ $syncRun->provider }}</span></td>
                            <td>{{ $syncRun->sync_type }}</td>
                            <td>
                                <span class="badge {{ $syncRun->status === 'success' ? 'bg-success' : ($syncRun->status === 'running' ? 'bg-info' : 'bg-danger') }}">
                                    {{ ucfirst($syncRun->status) }}
                                </span>
                            </td>
                            <td>{{ $syncRun->records_processed }}</td>
                            <td>{{ $syncRun->started_at?->format('M j, g:i A') }}</td>
                            <td>{{ $syncRun->finished_at?->format('M j, g:i A') ?? '-' }}</td>
                            <td>{{ $syncRun->duration ? round($syncRun->duration, 2) . 's' : '-' }}</td>
                            <td>
                                @if($syncRun->error_message)
                                    <span class="text-danger" title="{{ $syncRun->error_message }}">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No sync runs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($syncRuns->hasPages())
        <div class="card-footer bg-white">
            {{ $syncRuns->links() }}
        </div>
    @endif
</div>
@endsection
