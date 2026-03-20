@extends('layouts.admin')

@section('title', __('common.dashboard'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">{{ __('common.dashboard') }}</h1>
    <div class="text-muted">{{ now()->format('l, F j, Y') }}</div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">{{ __('common.active_accounts') }}</h6>
                        <h2 class="mb-0">{{ $totalAccounts }}</h2>
                    </div>
                    <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">{{ __('common.active_campaigns') }}</h6>
                        <h2 class="mb-0">{{ $totalCampaigns }}</h2>
                    </div>
                    <i class="bi bi-megaphone fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">{{ __('common.today_spend') }}</h6>
                        <h2 class="mb-0">${{ number_format($todaySpend, 2) }}</h2>
                    </div>
                    <i class="bi bi-currency-dollar fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stat-card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">{{ __('common.this_month') }}</h6>
                        <h2 class="mb-0">${{ number_format($monthSpend, 2) }}</h2>
                    </div>
                    <i class="bi bi-graph-up fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AI Recommendation Stats -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">{{ __('common.new_recommendations') }}</h6>
                        <h3 class="mb-0">{{ $newRecommendations }}</h3>
                        <small class="text-muted">{{ __('common.awaiting_review') }}</small>
                    </div>
                    <i class="bi bi-lightbulb fs-1 text-primary"></i>
                </div>
                <a href="{{ route('admin.recommendations.index', ['status' => 'new']) }}" class="btn btn-sm btn-outline-primary mt-3 w-100">
                    {{ __('common.view_all') }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">{{ __('common.high_critical_issues') }}</h6>
                        <h3 class="mb-0">{{ $highCriticalRecommendations }}</h3>
                        <small class="text-muted">{{ __('common.require_attention') }}</small>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
                </div>
                <a href="{{ route('admin.recommendations.index', ['severity' => 'high']) }}" class="btn btn-sm btn-outline-danger mt-3 w-100">
                    {{ __('common.view_issues') }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">{{ __('common.approved_last_7_days') }}</h6>
                        <h3 class="mb-0">{{ $approvedRecommendationsLast7Days }}</h3>
                        <small class="text-muted">{{ __('common.ready_for_execution') }}</small>
                    </div>
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                </div>
                <a href="{{ route('admin.recommendations.index', ['status' => 'approved']) }}" class="btn btn-sm btn-outline-success mt-3 w-100">
                    {{ __('common.view_approved') }}
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Sprint 3: Approvals & Publishing Stats -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">{{ __('common.pending_approvals') }}</h6>
                        <h3 class="mb-0">{{ $pendingApprovals }}</h3>
                        <small class="text-muted">{{ __('common.awaiting_decision') }}</small>
                    </div>
                    <i class="bi bi-clipboard-check fs-1 text-warning"></i>
                </div>
                <a href="{{ route('admin.approvals.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-warning mt-3 w-100">
                    {{ __('common.view_approvals') }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">Drafts Ready to Publish</h6>
                        <h3 class="mb-0">{{ $approvedDraftsWaitingPublish }}</h3>
                        <small class="text-muted">Approved drafts</small>
                    </div>
                    <i class="bi bi-file-earmark-check fs-1 text-info"></i>
                </div>
                <a href="{{ route('admin.campaign-drafts.index', ['status' => 'approved']) }}" class="btn btn-sm btn-outline-info mt-3 w-100">
                    View Drafts
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">Failed Publish Jobs (7d)</h6>
                        <h3 class="mb-0">{{ $failedPublishJobsLast7Days }}</h3>
                        <small class="text-muted">Need attention</small>
                    </div>
                    <i class="bi bi-x-circle fs-1 text-danger"></i>
                </div>
                <a href="{{ route('admin.publish-jobs.index', ['status' => 'failed']) }}" class="btn btn-sm btn-outline-danger mt-3 w-100">
                    View Failed Jobs
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Sprint 4: AI Activity Stats -->
@can('view_ai_usage_logs')
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">AI Runs Today</h6>
                        <h3 class="mb-0">{{ $aiRunsToday }}</h3>
                        <small class="text-muted">Successful generations</small>
                    </div>
                    <i class="bi bi-cpu fs-1 text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">Failed AI Runs (24h)</h6>
                        <h3 class="mb-0">{{ $aiRunsFailedToday }}</h3>
                        <small class="text-muted">Need investigation</small>
                    </div>
                    <i class="bi bi-exclamation-octagon fs-1 text-danger"></i>
                </div>
                @if($aiRunsFailedToday > 0)
                <a href="{{ route('admin.ai-usage-logs.index', ['status' => 'failed']) }}" class="btn btn-sm btn-outline-danger mt-3 w-100">
                    View Failed Runs
                </a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">Pending Enrichments</h6>
                        <h3 class="mb-0">{{ $pendingEnrichments }}</h3>
                        <small class="text-muted">Awaiting review</small>
                    </div>
                    <i class="bi bi-stars fs-1 text-warning"></i>
                </div>
                @if($pendingEnrichments > 0)
                <a href="{{ route('admin.campaign-drafts.index') }}" class="btn btn-sm btn-outline-warning mt-3 w-100">
                    Review Enrichments
                </a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase small">AI Cost (7d)</h6>
                        <h3 class="mb-0">${{ number_format($aiCostLast7Days, 2) }}</h3>
                        <small class="text-muted">Token usage cost</small>
                    </div>
                    <i class="bi bi-cash-coin fs-1 text-success"></i>
                </div>
                <a href="{{ route('admin.ai-usage-logs.index') }}" class="btn btn-sm btn-outline-success mt-3 w-100">
                    View Details
                </a>
            </div>
        </div>
    </div>
</div>
@endcan

<!-- Campaign Status Breakdown -->
<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">{{ __('common.campaign_status_breakdown') }}</h5>
            </div>
            <div class="card-body">
                @if(!empty($campaignsByStatus))
                    <table class="table table-sm">
                        <tbody>
                            @foreach($campaignsByStatus as $status => $count)
                                <tr>
                                    <td>
                                        <span class="badge {{ $status === 'active' ? 'bg-success' : ($status === 'paused' ? 'bg-warning' : 'bg-secondary') }}">
                                            {{ __('common.' . $status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">{{ $count }} {{ __('common.campaigns') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted">{{ __('common.no_campaigns_found') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">{{ __('common.spend_trend_7d') }}</h5>
            </div>
            <div class="card-body">
                @if($spendTrend->isNotEmpty())
                    <table class="table table-sm">
                        <tbody>
                            @foreach($spendTrend as $trend)
                                <tr>
                                    <td>{{ $trend->insight_date->format('M j, Y') }}</td>
                                    <td class="text-end">${{ number_format($trend->total_spend, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted">{{ __('common.no_spend_data') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Recent Agent Runs -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">{{ __('common.recent_ai_agent_runs') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('common.id') }}</th>
                                <th>{{ __('common.agent') }}</th>
                                <th>{{ __('common.scope') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.recommendations') }}</th>
                                <th>{{ __('common.started') }}</th>
                                <th>{{ __('common.duration') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAgentRuns as $run)
                                <tr>
                                    <td>#{{ $run->id }}</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ str_replace('_', ' ', ucwords($run->agent_name, '_')) }}
                                        </span>
                                    </td>
                                    <td>{{ ucfirst($run->scope_type) }}</td>
                                    <td>
                                        <span class="badge {{ $run->status === 'success' ? 'bg-success' : ($run->status === 'running' ? 'bg-info' : 'bg-danger') }}">
                                            {{ __('common.' . $run->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $run->recommendations->count() }}</td>
                                    <td>{{ $run->started_at?->format('M j, g:i A') }}</td>
                                    <td>{{ $run->duration ? round($run->duration, 2) . 's' : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">{{ __('common.no_agent_runs_found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Campaign Drafts -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Campaign Drafts</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Briefing</th>
                                <th>Template</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentDrafts as $draft)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.campaign-drafts.show', $draft) }}">
                                            {{ $draft->generated_name }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge {{ $draft->status === 'published' ? 'bg-primary' : ($draft->status === 'approved' ? 'bg-success' : 'bg-secondary') }}">
                                            {{ ucwords(str_replace('_', ' ', $draft->status)) }}
                                        </span>
                                    </td>
                                    <td>{{ $draft->briefing?->brand ?? 'N/A' }}</td>
                                    <td>{{ $draft->template?->name ?? 'N/A' }}</td>
                                    <td>{{ $draft->created_at->format('M j, g:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No drafts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sync Runs -->
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Recent Sync Runs</h5>
    </div>
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
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSyncRuns as $syncRun)
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
                            <td>{{ $syncRun->duration ? round($syncRun->duration, 2) . 's' : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No sync runs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
