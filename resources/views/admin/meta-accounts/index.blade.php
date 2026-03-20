@extends('layouts.admin')

@section('title', 'Meta Ad Accounts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Meta Ad Accounts</h1>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Account ID</th>
                        <th>Business</th>
                        <th>Currency</th>
                        <th>Timezone</th>
                        <th>Status</th>
                        <th>Campaigns</th>
                        <th>Last Synced</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td>
                                <a href="{{ route('admin.meta-accounts.show', $account) }}" class="text-decoration-none fw-semibold">
                                    {{ $account->name }}
                                </a>
                            </td>
                            <td><code>{{ $account->meta_account_id }}</code></td>
                            <td>{{ $account->business_name ?? '-' }}</td>
                            <td>{{ $account->currency ?? '-' }}</td>
                            <td>{{ $account->timezone_name ?? '-' }}</td>
                            <td>
                                @if($account->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $account->campaigns_count }}</td>
                            <td>{{ $account->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
                            <td>
                                <a href="{{ route('admin.meta-accounts.show', $account) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.meta-accounts.sync-campaigns', $account) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Sync Campaigns">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($accounts->hasPages())
        <div class="card-footer bg-white">
            {{ $accounts->links() }}
        </div>
    @endif
</div>
@endsection
