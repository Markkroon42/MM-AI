@extends('layouts.admin')

@section('title', 'Scheduled Task: ' . $task->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">{{ $task->name }}</h1>
    <div>
        @can('update', $task)
        <a href="{{ route('admin.scheduled-tasks.edit', $task) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit Task
        </a>
        @endcan
        <a href="{{ route('admin.scheduled-tasks.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
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

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Task Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Task Type:</strong>
                    </div>
                    <div class="col-md-9">
                        <code>{{ $task->task_type }}</code>
                    </div>
                </div>

                @if($task->description)
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Description:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $task->description }}
                    </div>
                </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Status:</strong>
                    </div>
                    <div class="col-md-9">
                        @php
                            $statusColors = [
                                'active' => 'success',
                                'paused' => 'warning',
                                'disabled' => 'secondary'
                            ];
                        @endphp
                        <span class="badge bg-{{ $statusColors[$task->status] ?? 'secondary' }} fs-6">
                            {{ ucfirst($task->status) }}
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Health Status:</strong>
                    </div>
                    <div class="col-md-9">
                        @php
                            $healthColors = [
                                'healthy' => 'success',
                                'degraded' => 'warning',
                                'unhealthy' => 'danger',
                                'inactive' => 'secondary'
                            ];
                        @endphp
                        <span class="badge bg-{{ $healthColors[$task->health_status] ?? 'secondary' }} fs-6">
                            {{ ucfirst($task->health_status) }}
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Cron Expression:</strong>
                    </div>
                    <div class="col-md-9">
                        <code>{{ $task->cron_expression }}</code>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Next Run:</strong>
                    </div>
                    <div class="col-md-9">
                        @if($task->next_run_at)
                            {{ $task->next_run_at->format('M d, Y H:i:s') }}
                            <span class="text-muted">({{ $task->next_run_at->diffForHumans() }})</span>
                        @else
                            <span class="text-muted">Not scheduled</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Last Run:</strong>
                    </div>
                    <div class="col-md-9">
                        @if($task->last_run_at)
                            {{ $task->last_run_at->format('M d, Y H:i:s') }}
                            <span class="text-muted">({{ $task->last_run_at->diffForHumans() }})</span>
                        @else
                            <span class="text-muted">Never executed</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Run Statistics:</strong>
                    </div>
                    <div class="col-md-9">
                        <span class="badge bg-primary">{{ $task->run_count }} total runs</span>
                        @if($task->failure_count > 0)
                            <span class="badge bg-danger">{{ $task->failure_count }} failures</span>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Alert on Failure:</strong>
                    </div>
                    <div class="col-md-9">
                        <span class="badge bg-{{ $task->alert_on_failure ? 'success' : 'secondary' }}">
                            {{ $task->alert_on_failure ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                </div>

                @if($task->run_context_json && count($task->run_context_json) > 0)
                <div class="row mb-0">
                    <div class="col-md-3">
                        <strong>Run Context:</strong>
                    </div>
                    <div class="col-md-9">
                        <pre class="bg-light p-3 rounded mb-0"><code>{{ json_encode($task->run_context_json, JSON_PRETTY_PRINT) }}</code></pre>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Execution History</h5>
            </div>
            <div class="card-body">
                @if($task->runs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Started At</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($task->runs as $run)
                            <tr>
                                <td>{{ $run->started_at->format('M d, H:i:s') }}</td>
                                <td>
                                    @if($run->finished_at)
                                        {{ $run->started_at->diffInSeconds($run->finished_at) }}s
                                    @else
                                        <span class="text-muted">Running...</span>
                                    @endif
                                </td>
                                <td>
                                    @if($run->status === 'success')
                                        <span class="badge bg-success">Success</span>
                                    @elseif($run->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($run->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($run->error_message)
                                        <span class="text-danger">{{ Str::limit($run->error_message, 80) }}</span>
                                    @elseif($run->output_summary)
                                        <span class="text-muted">{{ Str::limit($run->output_summary, 80) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted mb-0">No execution history available yet.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                @can('update', $task)
                    @if($task->status === 'paused')
                        <form method="POST" action="{{ route('admin.scheduled-tasks.resume', $task) }}" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-play-fill me-1"></i> Resume Task
                            </button>
                        </form>
                    @elseif($task->status === 'active')
                        <form method="POST" action="{{ route('admin.scheduled-tasks.pause', $task) }}" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="bi bi-pause-fill me-1"></i> Pause Task
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.scheduled-tasks.run-now', $task) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-play-circle me-1"></i> Run Now
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Metadata</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <small class="text-muted">Created:</small><br>
                    {{ $task->created_at->format('M d, Y H:i') }}
                </p>
                <p class="mb-0">
                    <small class="text-muted">Last Updated:</small><br>
                    {{ $task->updated_at->format('M d, Y H:i') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
