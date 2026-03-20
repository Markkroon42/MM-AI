@extends('layouts.admin')

@section('title', 'Publish Jobs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Publish Jobs</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="running">Running</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Action Type</label>
                <select name="action_type" class="form-select">
                    <option value="">All</option>
                    <option value="publish_campaign_draft">Publish Campaign Draft</option>
                    <option value="pause_campaign">Pause Campaign</option>
                    <option value="update_campaign_budget">Update Campaign Budget</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Action Type</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Executed At</th>
                        <th>Error</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        <tr>
                            <td>{{ $job->provider }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $job->action_type)) }}</td>
                            <td><span class="badge bg-{{ $job->status === 'success' ? 'success' : ($job->status === 'failed' ? 'danger' : 'warning') }}">{{ $job->status }}</span></td>
                            <td>{{ $job->attempts }}</td>
                            <td>{{ $job->executed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ $job->error_message ? Str::limit($job->error_message, 50) : '-' }}</td>
                            <td>
                                <a href="{{ route('admin.publish-jobs.show', $job) }}" class="btn btn-sm btn-outline-primary">View</a>
                                @if($job->status === 'failed')
                                    <form method="POST" action="{{ route('admin.publish-jobs.retry', $job) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning">Retry</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No jobs found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $jobs->links() }}</div>
    </div>
</div>
@endsection
