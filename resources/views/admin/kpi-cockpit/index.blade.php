@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">KPI Cockpit</h1>
        <div>
            <span class="badge bg-secondary">Last Updated: {{ now()->format('H:i') }}</span>
        </div>
    </div>

    {{-- Critical Alerts --}}
    @if($criticalAlerts > 0)
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div>
            <strong>{{ $criticalAlerts }} Critical Alert(s)</strong> require immediate attention.
            <a href="{{ route('admin.system-alerts.index', ['severity' => 'critical']) }}" class="alert-link ms-2">View Alerts</a>
        </div>
    </div>
    @endif

    {{-- Key Metrics Row --}}
    @if($latestSnapshot)
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Spend</h6>
                    <h3 class="mb-0">€{{ number_format($latestSnapshot->total_spend, 2) }}</h3>
                    @if($yesterdaySnapshot)
                    @php
                        $change = $latestSnapshot->total_spend - $yesterdaySnapshot->total_spend;
                        $isPositive = $change >= 0;
                    @endphp
                    <small class="text-{{ $isPositive ? 'success' : 'danger' }}">
                        <i class="bi bi-arrow-{{ $isPositive ? 'up' : 'down' }}"></i>
                        €{{ number_format(abs($change), 2) }}
                    </small>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Revenue</h6>
                    <h3 class="mb-0">€{{ number_format($latestSnapshot->total_revenue, 2) }}</h3>
                    @if($yesterdaySnapshot)
                    @php
                        $change = $latestSnapshot->total_revenue - $yesterdaySnapshot->total_revenue;
                        $isPositive = $change >= 0;
                    @endphp
                    <small class="text-{{ $isPositive ? 'success' : 'danger' }}">
                        <i class="bi bi-arrow-{{ $isPositive ? 'up' : 'down' }}"></i>
                        €{{ number_format(abs($change), 2) }}
                    </small>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">ROAS</h6>
                    <h3 class="mb-0">{{ number_format($latestSnapshot->avg_roas, 2) }}x</h3>
                    @if($yesterdaySnapshot)
                    @php
                        $change = $latestSnapshot->avg_roas - $yesterdaySnapshot->avg_roas;
                        $isPositive = $change >= 0;
                    @endphp
                    <small class="text-{{ $isPositive ? 'success' : 'danger' }}">
                        <i class="bi bi-arrow-{{ $isPositive ? 'up' : 'down' }}"></i>
                        {{ number_format(abs($change), 2) }}x
                    </small>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Active Campaigns</h6>
                    <h3 class="mb-0">{{ $latestSnapshot->active_campaigns_count }}</h3>
                    <small class="text-muted">
                        {{ $latestSnapshot->active_ad_sets_count }} ad sets, {{ $latestSnapshot->active_ads_count }} ads
                    </small>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-info">
        No KPI snapshot data available yet. Run <code>php artisan kpi:snapshot</code> to create your first snapshot.
    </div>
    @endif

    <div class="row g-3">
        {{-- System Health --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">System Health</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Open Alerts</span>
                        <span class="badge bg-{{ $openAlerts > 0 ? 'danger' : 'success' }}">{{ $openAlerts }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Scheduled Tasks</span>
                        <span class="badge bg-{{ $unhealthyTasks > 0 ? 'warning' : 'success' }}">
                            {{ $activeTasks - $unhealthyTasks }}/{{ $activeTasks }} Healthy
                        </span>
                    </div>
                    @if($latestSnapshot)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Pending Approvals</span>
                        <span class="badge bg-{{ $latestSnapshot->pending_approvals_count > 5 ? 'warning' : 'secondary' }}">
                            {{ $latestSnapshot->pending_approvals_count }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Pending Recommendations</span>
                        <span class="badge bg-info">{{ $latestSnapshot->pending_recommendations_count }}</span>
                    </div>
                    @endif
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('admin.system-alerts.index') }}" class="btn btn-sm btn-outline-primary w-100">
                        View All Alerts
                    </a>
                </div>
            </div>
        </div>

        {{-- Top Performers --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Top Performers</h5>
                </div>
                <div class="card-body">
                    @if(!empty($topCampaigns))
                        @foreach($topCampaigns as $campaign)
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="d-flex justify-content-between">
                                <strong class="text-truncate" style="max-width: 200px;" title="{{ $campaign['campaign_name'] }}">
                                    {{ $campaign['campaign_name'] }}
                                </strong>
                                <span class="badge bg-success">{{ number_format($campaign['roas'], 2) }}x</span>
                            </div>
                            <small class="text-muted">
                                €{{ number_format($campaign['spend'], 0) }} → €{{ number_format($campaign['revenue'], 0) }}
                            </small>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No performance data available yet.</p>
                    @endif
                </div>
                @if($latestReport)
                <div class="card-footer bg-white">
                    <a href="{{ route('admin.executive-reports.show', $latestReport) }}" class="btn btn-sm btn-outline-primary w-100">
                        View Full Report
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- Bottom Performers --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Needs Attention</h5>
                </div>
                <div class="card-body">
                    @if(!empty($bottomCampaigns))
                        @foreach($bottomCampaigns as $campaign)
                        <div class="mb-3 pb-2 border-bottom">
                            <div class="d-flex justify-content-between">
                                <strong class="text-truncate" style="max-width: 200px;" title="{{ $campaign['campaign_name'] }}">
                                    {{ $campaign['campaign_name'] }}
                                </strong>
                                <span class="badge bg-danger">{{ number_format($campaign['roas'], 2) }}x</span>
                            </div>
                            <small class="text-muted">
                                €{{ number_format($campaign['spend'], 0) }} → €{{ number_format($campaign['revenue'], 0) }}
                            </small>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No performance issues detected.</p>
                    @endif
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('admin.recommendations.index') }}" class="btn btn-sm btn-outline-warning w-100">
                        View Recommendations
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="row g-3 mt-3">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <a href="{{ route('admin.executive-reports.index') }}" class="btn btn-outline-primary w-100">
                                <i class="bi bi-file-earmark-text me-2"></i>Executive Reports
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.scheduled-tasks.index') }}" class="btn btn-outline-primary w-100">
                                <i class="bi bi-clock-history me-2"></i>Scheduled Tasks
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.guardrail-rules.index') }}" class="btn btn-outline-primary w-100">
                                <i class="bi bi-shield-check me-2"></i>Guardrail Rules
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('admin.recommendations.index') }}" class="btn btn-outline-primary w-100">
                                <i class="bi bi-lightbulb me-2"></i>Recommendations
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
