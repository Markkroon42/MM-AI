@extends('layouts.admin')

@section('title', 'Task Run Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Task Run Details</h1>
    <a href="{{ route('admin.scheduled-task-runs.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Run Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th style="width: 40%;">Task Name:</th>
                        <td>{{ $run->scheduledTask->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Task Type:</th>
                        <td><code>{{ $run->scheduledTask->task_type ?? 'N/A' }}</code></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
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
                    </tr>
                    <tr>
                        <th>Started At:</th>
                        <td>
                            {{ $run->started_at->format('M d, Y H:i:s') }}
                            <br><small class="text-muted">{{ $run->started_at->diffForHumans() }}</small>
                        </td>
                    </tr>
                    <tr>
                        <th>Completed At:</th>
                        <td>
                            @if($run->completed_at)
                                {{ $run->completed_at->format('M d, Y H:i:s') }}
                                <br><small class="text-muted">{{ $run->completed_at->diffForHumans() }}</small>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Duration:</th>
                        <td>
                            @if($run->duration_seconds)
                                {{ number_format($run->duration_seconds, 2) }} seconds
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        @if($run->error_message)
        <div class="card mb-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Error Message</h5>
            </div>
            <div class="card-body">
                <pre class="mb-0" style="white-space: pre-wrap;">{{ $run->error_message }}</pre>
            </div>
        </div>
        @endif
    </div>
</div>

@if($run->output)
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Output</h5>
    </div>
    <div class="card-body">
        <pre class="mb-0" style="white-space: pre-wrap; max-height: 500px; overflow-y: auto;">{{ $run->output }}</pre>
    </div>
</div>
@endif
@endsection
