@extends('layouts.admin')

@section('title', $campaign->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.campaigns.index') }}">Campaigns</a></li>
                <li class="breadcrumb-item active">{{ $campaign->name }}</li>
            </ol>
        </nav>
        <h1 class="h2 mb-0">{{ $campaign->name }}</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small">Total Spend</h6>
                <h3 class="mb-0">\${{ number_format($totalSpend, 2) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small">Impressions</h6>
                <h3 class="mb-0">{{ number_format($totalImpressions) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small">Clicks</h6>
                <h3 class="mb-0">{{ number_format($totalClicks) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body">
                <h6 class="text-muted text-uppercase small">ROAS</h6>
                <h3 class="mb-0">{{ number_format($avgRoas, 2) }}x</h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Ad Sets</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Daily Budget</th>
                                <th>Ads</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaign->adSets as $adSet)
                                <tr>
                                    <td>{{ $adSet->name }}</td>
                                    <td>
                                        <span class="badge {{ $adSet->status === 'active' ? 'bg-success' : 'bg-warning' }}">
                                            {{ ucfirst($adSet->status ?? 'unknown') }}
                                        </span>
                                    </td>
                                    <td>\${{ $adSet->daily_budget ? number_format($adSet->daily_budget, 2) : '-' }}</td>
                                    <td>{{ $adSet->ads->count() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No ad sets found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Campaign Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tbody>
                        <tr>
                            <td class="fw-semibold">Account</td>
                            <td>{{ $campaign->metaAdAccount->name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Objective</td>
                            <td>{{ $campaign->objective ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Status</td>
                            <td>
                                <span class="badge {{ $campaign->status === 'active' ? 'bg-success' : 'bg-warning' }}">
                                    {{ ucfirst($campaign->status ?? 'unknown') }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-semibold">Daily Budget</td>
                            <td>\${{ $campaign->daily_budget ? number_format($campaign->daily_budget, 2) : '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- AI Recommendations Section -->
@php
    $campaignRecommendations = $campaign->recommendations()
        ->orderBy('severity', 'desc')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    $openRecommendations = $campaignRecommendations->whereIn('status', ['new', 'reviewing'])->count();
    $reviewedRecommendations = $campaignRecommendations->whereIn('status', ['approved', 'rejected'])->count();
@endphp

@if($campaignRecommendations->isNotEmpty())
<div class="card mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">AI Recommendations</h5>
        <div>
            <span class="badge bg-primary me-2">{{ $openRecommendations }} Open</span>
            <span class="badge bg-secondary">{{ $reviewedRecommendations }} Reviewed</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Severity</th>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaignRecommendations as $rec)
                        <tr>
                            <td>
                                @php
                                    $severityClass = match($rec->severity) {
                                        'critical' => 'bg-dark',
                                        'high' => 'bg-danger',
                                        'medium' => 'bg-warning',
                                        'low' => 'bg-info',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $severityClass }}">{{ ucfirst($rec->severity) }}</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ str_replace('_', ' ', ucwords($rec->recommendation_type, '_')) }}
                                </span>
                            </td>
                            <td>{{ Str::limit($rec->title, 60) }}</td>
                            <td>
                                @php
                                    $statusClass = match($rec->status) {
                                        'new' => 'bg-primary',
                                        'reviewing' => 'bg-warning',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ ucfirst($rec->status) }}</span>
                            </td>
                            <td>{{ $rec->created_at->diffForHumans() }}</td>
                            <td>
                                <a href="{{ route('admin.recommendations.show', $rec) }}" class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Daily Insights (Last 30 Days)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Impressions</th>
                        <th>Clicks</th>
                        <th>Spend</th>
                        <th>CPC</th>
                        <th>CTR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($insights as $insight)
                        <tr>
                            <td>{{ $insight->insight_date->format('M j, Y') }}</td>
                            <td>{{ number_format($insight->impressions) }}</td>
                            <td>{{ number_format($insight->clicks) }}</td>
                            <td>\${{ number_format($insight->spend, 2) }}</td>
                            <td>\${{ number_format($insight->cpc, 2) }}</td>
                            <td>{{ number_format($insight->ctr, 2) }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No insights data available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
