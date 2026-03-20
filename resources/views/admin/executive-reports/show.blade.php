@extends('layouts.admin')

@section('title', 'Executive Report: ' . $report->period_label)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 mb-1">Executive Report</h1>
        <p class="text-muted mb-0">{{ $report->period_label }}</p>
    </div>
    <div>
        <a href="{{ route('admin.executive-reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Reports
        </a>
    </div>
</div>

@if(!$report->isCompleted())
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    This report is {{ $report->status }}. Some data may be incomplete.
</div>
@endif

{{-- Executive Summary --}}
@if($report->executive_summary)
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-file-text me-2"></i>Executive Summary</h5>
    </div>
    <div class="card-body">
        <div class="fs-5">{{ $report->executive_summary }}</div>
    </div>
</div>
@endif

{{-- Headline Metrics --}}
@if($report->headline_metrics_json && count($report->headline_metrics_json) > 0)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Headline Metrics</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            @foreach($report->headline_metrics_json as $metric => $value)
                <div class="col-md-3">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">{{ ucfirst(str_replace('_', ' ', $metric)) }}</h6>
                            <h3 class="mb-0">
                                @if(is_numeric($value))
                                    @if(str_contains($metric, 'spend') || str_contains($metric, 'revenue') || str_contains($metric, 'cost'))
                                        €{{ number_format($value, 2) }}
                                    @elseif(str_contains($metric, 'roas') || str_contains($metric, 'ctr'))
                                        {{ number_format($value, 2) }}
                                    @else
                                        {{ number_format($value) }}
                                    @endif
                                @else
                                    {{ $value }}
                                @endif
                            </h3>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="row">
    {{-- Highlights --}}
    @if($report->highlights_json && count($report->highlights_json) > 0)
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-trophy me-2"></i>Highlights</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @foreach($report->highlights_json as $highlight)
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>{{ $highlight }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    {{-- Issues --}}
    @if($report->issues_json && count($report->issues_json) > 0)
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Issues & Concerns</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @foreach($report->issues_json as $issue)
                        <li class="mb-2">
                            <i class="bi bi-exclamation-circle-fill text-warning me-2"></i>{{ $issue }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="row">
    {{-- Top Performers --}}
    @if($report->top_performers_json && count($report->top_performers_json) > 0)
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-star-fill text-warning me-2"></i>Top Performers</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach($report->top_performers_json as $performer)
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <strong>{{ $performer['name'] ?? $performer['campaign_name'] ?? 'Unknown' }}</strong>
                                    @if(isset($performer['details']))
                                        <br><small class="text-muted">{{ $performer['details'] }}</small>
                                    @endif
                                </div>
                                @if(isset($performer['roas']))
                                    <span class="badge bg-success fs-6">{{ number_format($performer['roas'], 2) }}x ROAS</span>
                                @elseif(isset($performer['metric']))
                                    <span class="badge bg-primary fs-6">{{ $performer['metric'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Bottom Performers --}}
    @if($report->bottom_performers_json && count($report->bottom_performers_json) > 0)
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-arrow-down-circle text-danger me-2"></i>Needs Attention</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach($report->bottom_performers_json as $performer)
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <strong>{{ $performer['name'] ?? $performer['campaign_name'] ?? 'Unknown' }}</strong>
                                    @if(isset($performer['details']))
                                        <br><small class="text-muted">{{ $performer['details'] }}</small>
                                    @endif
                                </div>
                                @if(isset($performer['roas']))
                                    <span class="badge bg-danger fs-6">{{ number_format($performer['roas'], 2) }}x ROAS</span>
                                @elseif(isset($performer['metric']))
                                    <span class="badge bg-warning fs-6">{{ $performer['metric'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Priorities --}}
@if($report->priorities_json && count($report->priorities_json) > 0)
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Recommended Actions & Priorities</h5>
    </div>
    <div class="card-body">
        <ol class="mb-0">
            @foreach($report->priorities_json as $priority)
                <li class="mb-2">{{ $priority }}</li>
            @endforeach
        </ol>
    </div>
</div>
@endif

{{-- Report Metadata --}}
<div class="card border-secondary">
    <div class="card-header bg-light">
        <h5 class="mb-0">Report Metadata</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Report Type:</strong>
                <p>{{ ucfirst(str_replace('_', ' ', $report->report_type)) }}</p>
            </div>
            <div class="col-md-3">
                <strong>Period:</strong>
                <p>{{ $report->period_start->format('M d, Y') }} - {{ $report->period_end->format('M d, Y') }}</p>
            </div>
            <div class="col-md-3">
                <strong>Generated:</strong>
                <p>{{ $report->generated_at ? $report->generated_at->format('M d, Y H:i:s') : 'N/A' }}</p>
            </div>
            <div class="col-md-3">
                <strong>Generation Time:</strong>
                <p>{{ $report->generation_duration_seconds ? number_format($report->generation_duration_seconds, 2) . 's' : 'N/A' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
