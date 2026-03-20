@extends('layouts.admin')

@section('title', $account->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.meta-accounts.index') }}">Meta Accounts</a></li>
                <li class="breadcrumb-item active">{{ $account->name }}</li>
            </ol>
        </nav>
        <h1 class="h2 mb-0">{{ $account->name }}</h1>
    </div>
    <div>
        <form method="POST" action="{{ route('admin.meta-accounts.sync-campaigns', $account) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-arrow-repeat"></i> Sync Campaigns
            </button>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Account Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td class="fw-semibold" style="width: 200px;">Meta Account ID</td>
                            <td><code>{{ $account->meta_account_id }}</code></td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Business Name</td>
                            <td>{{ $account->business_name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Currency</td>
                            <td>{{ $account->currency ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Status</td>
                            <td>
                                @if($account->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="text-uppercase text-muted mb-3">Last 30 Days</h6>
                <div class="mb-3">
                    <div class="text-muted small">Total Spend</div>
                    <div class="h4 mb-0">\${{ number_format($totalSpend, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Campaigns</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($account->campaigns as $campaign)
                        <tr>
                            <td>{{ $campaign->name }}</td>
                            <td><span class="badge bg-success">{{ $campaign->status }}</span></td>
                            <td><a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No campaigns found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
