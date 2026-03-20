@extends('layouts.admin')

@section('title', 'Create Scheduled Task')

@section('content')
<div class="mb-4">
    <h1 class="h2">Create Scheduled Task</h1>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Validation Error:</strong>
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.scheduled-tasks.store') }}">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Task Name *</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="task_type" class="form-label">Task Type *</label>
                <select name="task_type" id="task_type" class="form-select @error('task_type') is-invalid @enderror" required>
                    <option value="">-- Select Task Type --</option>
                    <option value="run_agent" {{ old('task_type') == 'run_agent' ? 'selected' : '' }}>Run Agent</option>
                    <option value="generate_report" {{ old('task_type') == 'generate_report' ? 'selected' : '' }}>Generate Report</option>
                    <option value="create_kpi_snapshot" {{ old('task_type') == 'create_kpi_snapshot' ? 'selected' : '' }}>Create KPI Snapshot</option>
                    <option value="sync_meta" {{ old('task_type') == 'sync_meta' ? 'selected' : '' }}>Sync Meta Data</option>
                    <option value="cleanup_old_data" {{ old('task_type') == 'cleanup_old_data' ? 'selected' : '' }}>Cleanup Old Data</option>
                </select>
                @error('task_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="cron_expression" class="form-label">Cron Expression *</label>
                    <input type="text" name="cron_expression" id="cron_expression"
                           class="form-control font-monospace @error('cron_expression') is-invalid @enderror"
                           value="{{ old('cron_expression', '0 * * * *') }}" required>
                    @error('cron_expression')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">
                        Examples: <code>0 * * * *</code> (hourly), <code>0 0 * * *</code> (daily)
                    </small>
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label">Status *</label>
                    <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="paused" {{ old('status') == 'paused' ? 'selected' : '' }}>Paused</option>
                        <option value="disabled" {{ old('status') == 'disabled' ? 'selected' : '' }}>Disabled</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="run_context_json" class="form-label">Run Context (JSON)</label>
                <textarea name="run_context_json" id="run_context_json" rows="6"
                          class="form-control font-monospace @error('run_context_json') is-invalid @enderror">{{ old('run_context_json', '{}') }}</textarea>
                @error('run_context_json')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    Optional JSON configuration for the task. Example: <code>{"account_id": 123, "days": 7}</code>
                </small>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input type="checkbox" name="alert_on_failure" id="alert_on_failure" value="1"
                           class="form-check-input" {{ old('alert_on_failure', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="alert_on_failure">
                        Send alert notifications on failure
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.scheduled-tasks.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Create Scheduled Task
                </button>
            </div>
        </form>
    </div>
</div>

<div class="mt-4">
    <div class="card border-info">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>Cron Expression Help</h5>
            <p class="mb-2">Cron expressions consist of 5 fields:</p>
            <code>* * * * *</code>
            <p class="mb-2 mt-2">Which represent:</p>
            <ul class="mb-2">
                <li><strong>Minute</strong> (0-59)</li>
                <li><strong>Hour</strong> (0-23)</li>
                <li><strong>Day of Month</strong> (1-31)</li>
                <li><strong>Month</strong> (1-12)</li>
                <li><strong>Day of Week</strong> (0-7, where 0 and 7 are Sunday)</li>
            </ul>
            <p class="mb-1"><strong>Common Examples:</strong></p>
            <ul class="mb-0">
                <li><code>0 * * * *</code> - Every hour at minute 0</li>
                <li><code>0 0 * * *</code> - Daily at midnight</li>
                <li><code>0 9 * * 1</code> - Every Monday at 9 AM</li>
                <li><code>*/15 * * * *</code> - Every 15 minutes</li>
            </ul>
        </div>
    </div>
</div>
@endsection
