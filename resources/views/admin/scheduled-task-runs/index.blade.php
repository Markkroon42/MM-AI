@extends('layouts.admin')

@section('title', 'Task Run History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Task Run History</h1>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Task Name</th>
                        <th>Status</th>
                        <th>Started At</th>
                        <th>Completed At</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $run)
                        <tr>
                            <td>
                                <strong>{{ $run->scheduledTask->name ?? 'N/A' }}</strong>
                                <br><small class="text-muted">{{ $run->scheduledTask->task_type ?? 'N/A' }}</small>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'secondary',
                                        'running' => 'primary',
                                        'completed' => 'success',
                                        'failed' => 'danger'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$run->status] ?? 'secondary' }}">
                                    {{ ucfirst($run->status) }}
                                </span>
                            </td>
                            <td>
                                {{ $run->started_at->format('M d, Y H:i:s') }}
                                <br><small class="text-muted">{{ $run->started_at->diffForHumans() }}</small>
                            </td>
                            <td>
                                @if($run->completed_at)
                                    {{ $run->completed_at->format('M d, Y H:i:s') }}
                                    <br><small class="text-muted">{{ $run->completed_at->diffForHumans() }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($run->duration_seconds)
                                    {{ number_format($run->duration_seconds, 2) }}s
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.scheduled-task-runs.show', $run) }}"
                                   class="btn btn-sm btn-outline-primary" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No task runs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $runs->links() }}
        </div>
    </div>
</div>
@endsection
