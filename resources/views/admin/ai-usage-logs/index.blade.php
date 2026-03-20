@extends('layouts.admin')

@section('title', 'AI Usage Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">AI Usage Logs</h1>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.ai-usage-logs.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="RUNNING" {{ request('status') == 'RUNNING' ? 'selected' : '' }}>Running</option>
                    <option value="SUCCESS" {{ request('status') == 'SUCCESS' ? 'selected' : '' }}>Success</option>
                    <option value="FAILED" {{ request('status') == 'FAILED' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Agent</label>
                <select name="agent_name" class="form-select">
                    <option value="">All</option>
                    @foreach($agentNames as $agent)
                        <option value="{{ $agent }}" {{ request('agent_name') == $agent ? 'selected' : '' }}>
                            {{ $agent }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary me-2">Filter</button>
                <a href="{{ route('admin.ai-usage-logs.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Agent</th>
                        <th>Config</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Tokens</th>
                        <th>Cost</th>
                        <th>Started</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>#{{ $log->id }}</td>
                        <td><span class="badge bg-primary">{{ $log->agent_name }}</span></td>
                        <td>
                            @if($log->promptConfig)
                                <small>{{ $log->promptConfig->name }}</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($log->source_type)
                                <small>{{ class_basename($log->source_type) }} #{{ $log->source_id }}</small>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td>
                            @if($log->status == 'SUCCESS')
                                <span class="badge bg-success">Success</span>
                            @elseif($log->status == 'FAILED')
                                <span class="badge bg-danger">Failed</span>
                            @else
                                <span class="badge bg-info">Running</span>
                            @endif
                        </td>
                        <td>
                            <small>{{ number_format($log->tokens_input + $log->tokens_output) }}</small>
                        </td>
                        <td>
                            ${{ number_format($log->cost_estimate, 4) }}
                        </td>
                        <td>
                            @if($log->started_at)
                                <small>{{ $log->started_at->format('M d, H:i') }}</small>
                            @endif
                        </td>
                        <td>
                            @if($log->started_at && $log->finished_at)
                                <small>{{ $log->started_at->diffForHumans($log->finished_at, true) }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.ai-usage-logs.show', $log) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">No logs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
