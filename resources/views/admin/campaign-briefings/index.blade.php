@extends('layouts.admin')

@section('title', 'Campaign Briefings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Campaign Briefings</h1>
    <a href="{{ route('admin.campaign-briefings.create') }}" class="btn btn-primary">
        Create New Briefing
    </a>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.campaign-briefings.index') }}" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="ready_for_generation" {{ request('status') === 'ready_for_generation' ? 'selected' : '' }}>Ready for Generation</option>
                    <option value="generated" {{ request('status') === 'generated' ? 'selected' : '' }}>Generated</option>
                    <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>

            <div class="col-md-3">
                <label for="brand" class="form-label">Brand</label>
                <input type="text" name="brand" id="brand" class="form-control" value="{{ request('brand') }}" placeholder="Filter by brand">
            </div>

            <div class="col-md-3">
                <label for="market" class="form-label">Market</label>
                <input type="text" name="market" id="market" class="form-control" value="{{ request('market') }}" placeholder="Filter by market">
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary me-2">Filter</button>
                <a href="{{ route('admin.campaign-briefings.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Briefings Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Brand</th>
                        <th>Market</th>
                        <th>Objective</th>
                        <th>Budget</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($briefings as $briefing)
                        <tr>
                            <td>{{ $briefing->brand }}</td>
                            <td>{{ $briefing->market }}</td>
                            <td>{{ $briefing->objective }}</td>
                            <td>${{ number_format($briefing->budget_amount, 2) }}</td>
                            <td>
                                <span class="badge {{ $briefing->status === 'draft' ? 'bg-secondary' : ($briefing->status === 'generated' ? 'bg-success' : 'bg-info') }}">
                                    {{ ucwords(str_replace('_', ' ', $briefing->status)) }}
                                </span>
                            </td>
                            <td>{{ $briefing->creator->name ?? 'N/A' }}</td>
                            <td>{{ $briefing->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.campaign-briefings.show', $briefing) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No briefings found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $briefings->links() }}
        </div>
    </div>
</div>
@endsection
