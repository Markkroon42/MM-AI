@extends('layouts.admin')

@section('title', 'Scheduled Tasks')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Scheduled Tasks</h1>
    @can('create', App\Models\ScheduledTask::class)
    <a href="{{ route('admin.scheduled-tasks.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Create Task
    </a>
    @endcan
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
                        <th>Name</th>
                        <th>Type</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Health</th>
                        <th>Last Run</th>
                        <th>Next Run</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>
                                <strong>{{ $task->name }}</strong>
                                @if($task->description)
                                    <br><small class="text-muted">{{ Str::limit($task->description, 40) }}</small>
                                @endif
                            </td>
                            <td>
                                <code>{{ $task->task_type }}</code>
                            </td>
                            <td>
                                <code>{{ $task->cron_expression }}</code>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'active' => 'success',
                                        'paused' => 'warning',
                                        'disabled' => 'secondary'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$task->status] ?? 'secondary' }}">
                                    {{ ucfirst($task->status) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $healthColors = [
                                        'healthy' => 'success',
                                        'degraded' => 'warning',
                                        'unhealthy' => 'danger',
                                        'inactive' => 'secondary'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $healthColors[$task->health_status] ?? 'secondary' }}">
                                    {{ ucfirst($task->health_status) }}
                                </span>
                                @if($task->failure_count > 0)
                                    <br><small class="text-danger">{{ $task->failure_count }} failures</small>
                                @endif
                            </td>
                            <td>
                                @if($task->last_run_at)
                                    {{ $task->last_run_at->diffForHumans() }}
                                    <br><small class="text-muted">{{ $task->last_run_at->format('M d, H:i') }}</small>
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </td>
                            <td>
                                @if($task->next_run_at)
                                    {{ $task->next_run_at->diffForHumans() }}
                                    <br><small class="text-muted">{{ $task->next_run_at->format('M d, H:i') }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.scheduled-tasks.show', $task) }}"
                                       class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('update', $task)
                                        @if($task->status === 'paused')
                                            <form method="POST" action="{{ route('admin.scheduled-tasks.resume', $task) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Resume">
                                                    <i class="bi bi-play-fill"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.scheduled-tasks.pause', $task) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning" title="Pause">
                                                    <i class="bi bi-pause-fill"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.scheduled-tasks.run-now', $task) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-info" title="Run Now">
                                                <i class="bi bi-play-circle"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No scheduled tasks found.
                                @can('create', App\Models\ScheduledTask::class)
                                    <a href="{{ route('admin.scheduled-tasks.create') }}">Create your first task</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $tasks->links() }}
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="card border-info">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>About Scheduled Tasks</h5>
            <p class="card-text mb-2">
                Scheduled tasks run automatically based on their cron expressions. Monitor their health status to ensure smooth operations.
            </p>
            <ul class="mb-0">
                <li><strong>Active:</strong> Task is running on schedule</li>
                <li><strong>Paused:</strong> Task is temporarily stopped</li>
                <li><strong>Disabled:</strong> Task is permanently disabled</li>
                <li><strong>Health:</strong> Based on recent failure count (3+ failures = unhealthy)</li>
            </ul>
        </div>
    </div>
</div>
@endsection
