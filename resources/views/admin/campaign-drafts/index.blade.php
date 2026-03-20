@extends('layouts.admin')

@section('title', 'Campaign Drafts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Campaign Drafts</h1>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.campaign-drafts.index') }}" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="ready_for_review">Ready for Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="publishing">Publishing</option>
                    <option value="published">Published</option>
                    <option value="failed">Failed</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary me-2">Filter</button>
                <a href="{{ route('admin.campaign-drafts.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Drafts Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Briefing</th>
                        <th>Template</th>
                        <th>Approved By</th>
                        <th>Published At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drafts as $draft)
                        <tr>
                            <td>{{ $draft->generated_name }}</td>
                            <td>
                                <span class="badge {{ $draft->status === 'published' ? 'bg-primary' : ($draft->status === 'approved' ? 'bg-success' : ($draft->status === 'failed' ? 'bg-danger' : 'bg-secondary')) }}">
                                    {{ ucwords(str_replace('_', ' ', $draft->status)) }}
                                </span>
                            </td>
                            <td>{{ $draft->briefing?->brand ?? 'N/A' }}</td>
                            <td>{{ $draft->template?->name ?? 'N/A' }}</td>
                            <td>{{ $draft->approver?->name ?? '-' }}</td>
                            <td>{{ $draft->published_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.campaign-drafts.show', $draft) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No drafts found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $drafts->links() }}
        </div>
    </div>
</div>
@endsection
