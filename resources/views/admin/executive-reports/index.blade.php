@extends('layouts.admin')

@section('title', 'Executive Reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Executive Reports</h1>
    <div class="btn-group">
        <form method="POST" action="{{ route('admin.executive-reports.generate-daily') }}" class="me-2">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-file-earmark-text me-1"></i> Generate Daily Report
            </button>
        </form>
        <form method="POST" action="{{ route('admin.executive-reports.generate-weekly') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-calendar-week me-1"></i> Generate Weekly Report
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Generated</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td>
                                <strong>{{ $report->period_label }}</strong>
                                <br><small class="text-muted">
                                    {{ $report->period_start->format('Y-m-d') }} to {{ $report->period_end->format('Y-m-d') }}
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $report->report_type)) }}</span>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'completed' => 'success',
                                        'generating' => 'warning',
                                        'failed' => 'danger'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$report->status] ?? 'secondary' }}">
                                    {{ ucfirst($report->status) }}
                                </span>
                            </td>
                            <td>
                                @if($report->generated_at)
                                    {{ $report->generated_at->diffForHumans() }}
                                    <br><small class="text-muted">{{ $report->generated_at->format('M d, H:i') }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($report->generation_duration_seconds)
                                    {{ number_format($report->generation_duration_seconds, 1) }}s
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($report->isCompleted())
                                    <a href="{{ route('admin.executive-reports.show', $report) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> View Report
                                    </a>
                                @elseif($report->isFailed())
                                    <span class="text-danger" title="{{ $report->error_message }}">
                                        <i class="bi bi-exclamation-triangle"></i> Failed
                                    </span>
                                @else
                                    <span class="text-muted">Generating...</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No executive reports found. Generate your first report using the buttons above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $reports->links() }}
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="card border-info">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>About Executive Reports</h5>
            <p class="card-text mb-2">
                Executive reports provide high-level summaries of campaign performance, key metrics, and actionable insights.
            </p>
            <ul class="mb-0">
                <li><strong>Daily Reports:</strong> Generated for the previous day's performance</li>
                <li><strong>Weekly Reports:</strong> Generated for the previous week (Monday-Sunday)</li>
                <li><strong>Automated Generation:</strong> Reports can be scheduled via Scheduled Tasks</li>
            </ul>
        </div>
    </div>
</div>
@endsection
