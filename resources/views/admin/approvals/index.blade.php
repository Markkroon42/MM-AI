@extends('layouts.admin')

@section('title', 'Approvals')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Approvals</h1>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.approvals.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>

            <div class="col-md-3">
                <label for="approval_type" class="form-label">Type</label>
                <select name="approval_type" id="approval_type" class="form-select">
                    <option value="">All Types</option>
                    <option value="recommendation_execution">Recommendation Execution</option>
                    <option value="campaign_draft_publish">Campaign Draft Publish</option>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary me-2">Filter</button>
                <a href="{{ route('admin.approvals.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Approvals Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Approvable</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Decided By</th>
                        <th>Requested At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($approvals as $approval)
                        <tr>
                            <td>{{ ucwords(str_replace('_', ' ', $approval->approval_type)) }}</td>
                            <td>{{ class_basename($approval->approvable_type) }} #{{ $approval->approvable_id }}</td>
                            <td>
                                <span class="badge bg-{{ $approval->status === 'approved' ? 'success' : ($approval->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($approval->status) }}
                                </span>
                            </td>
                            <td>{{ $approval->requester?->name ?? 'System' }}</td>
                            <td>{{ $approval->approver?->name ?? $approval->rejector?->name ?? '-' }}</td>
                            <td>{{ $approval->requested_at?->format('Y-m-d H:i') ?? $approval->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.approvals.show', $approval) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No approvals found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $approvals->links() }}
        </div>
    </div>
</div>
@endsection
