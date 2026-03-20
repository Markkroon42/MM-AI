@extends('layouts.admin')

@section('title', 'Campaigns')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Campaigns</h1>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.campaigns.index') }}" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search campaigns..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="account_id" class="form-select">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="paused" {{ request('status') == 'paused' ? 'selected' : '' }}>Paused</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.campaigns.index') }}" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Account</th>
                        <th>Objective</th>
                        <th>Status</th>
                        <th>Daily Budget</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                        <tr>
                            <td>
                                <a href="{{ route('admin.campaigns.show', $campaign) }}" class="text-decoration-none fw-semibold">
                                    {{ $campaign->name }}
                                </a>
                            </td>
                            <td>{{ $campaign->metaAdAccount->name }}</td>
                            <td>{{ $campaign->objective ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $campaign->status === 'active' ? 'bg-success' : 'bg-warning' }}">
                                    {{ ucfirst($campaign->status ?? 'unknown') }}
                                </span>
                            </td>
                            <td>\${{ $campaign->daily_budget ? number_format($campaign->daily_budget, 2) : '-' }}</td>
                            <td>
                                <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No campaigns found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($campaigns->hasPages())
        <div class="card-footer bg-white">
            {{ $campaigns->links() }}
        </div>
    @endif
</div>
@endsection
