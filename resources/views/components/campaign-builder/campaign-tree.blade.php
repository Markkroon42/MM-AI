@props(['structure'])

@php
    $campaign = $structure['campaign'] ?? [];
    $adSets = $structure['ad_sets'] ?? [];
    $ads = $structure['ads'] ?? [];
    $summary = $structure['summary'] ?? [];
@endphp

<div class="campaign-tree">
    {{-- Campaign Card --}}
    <div class="campaign-card card border-primary mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">
                        <i class="bi bi-megaphone text-primary me-2"></i>
                        {{ $campaign['name'] }}
                    </h5>
                    <div class="text-muted small">
                        <span class="badge bg-primary me-2">{{ $campaign['objective'] ?? 'N/A' }}</span>
                        <span class="badge bg-secondary">{{ $campaign['status'] ?? 'PAUSED' }}</span>
                    </div>
                </div>
                <div class="text-end">
                    @if($campaign['daily_budget'])
                        <div class="fw-bold text-success">€{{ number_format($campaign['daily_budget'], 2) }}/day</div>
                    @elseif($campaign['lifetime_budget'])
                        <div class="fw-bold text-success">€{{ number_format($campaign['lifetime_budget'], 2) }} total</div>
                    @endif
                    @if($campaign['budget_optimization'])
                        <div class="small text-muted">CBO Enabled</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Ad Sets --}}
    @if(count($adSets) > 0)
        <div class="ad-sets-container ms-4">
            @foreach($adSets as $adSet)
                <div class="ad-set-card card border-info mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <i class="bi bi-bullseye text-info me-2"></i>
                                    {{ $adSet['name'] }}
                                </h6>
                                <div class="small text-muted mb-2">
                                    {{ $adSet['targeting']['summary'] ?? 'No targeting' }}
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="badge bg-{{ $adSet['completeness'] >= 75 ? 'success' : ($adSet['completeness'] >= 50 ? 'warning' : 'danger') }}">
                                    {{ $adSet['completeness'] }}%
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 small">
                            <div class="col-auto">
                                <span class="text-muted">Goal:</span>
                                <strong>{{ $adSet['optimization_goal'] ?? 'N/A' }}</strong>
                            </div>
                            @if($adSet['daily_budget'])
                                <div class="col-auto">
                                    <span class="text-muted">Budget:</span>
                                    <strong>€{{ number_format($adSet['daily_budget'], 2) }}</strong>
                                </div>
                            @endif
                        </div>

                        {{-- Ads within this ad set --}}
                        @php
                            $adSetAds = array_filter($ads, fn($ad) => ($ad['ad_set_index'] ?? 0) === $adSet['index']);
                        @endphp

                        @if(count($adSetAds) > 0)
                            <div class="ads-container mt-3 ms-3">
                                @foreach($adSetAds as $ad)
                                    <div class="ad-card card bg-light border-0 mb-2">
                                        <div class="card-body py-2 px-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="flex-grow-1">
                                                    <div class="small fw-bold">
                                                        <i class="bi bi-image me-1"></i>
                                                        {{ $ad['name'] }}
                                                    </div>
                                                    <div class="very-small text-muted">
                                                        @if($ad['has_copy'])
                                                            <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                        @else
                                                            <i class="bi bi-circle text-muted me-1"></i>
                                                        @endif
                                                        Copy

                                                        @if($ad['has_headline'])
                                                            <i class="bi bi-check-circle-fill text-success ms-2 me-1"></i>
                                                        @else
                                                            <i class="bi bi-circle text-muted ms-2 me-1"></i>
                                                        @endif
                                                        Headline

                                                        @if($ad['has_cta'])
                                                            <i class="bi bi-check-circle-fill text-success ms-2 me-1"></i>
                                                        @else
                                                            <i class="bi bi-circle text-muted ms-2 me-1"></i>
                                                        @endif
                                                        CTA
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="badge bg-{{ $ad['completeness'] >= 75 ? 'success' : ($ad['completeness'] >= 50 ? 'warning' : 'danger') }} small">
                                                        {{ $ad['completeness'] }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-warning small mt-3 mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                No ads in this ad set
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            No ad sets defined yet
        </div>
    @endif
</div>

<style>
.campaign-tree {
    position: relative;
}

.campaign-card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.ad-set-card {
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.ad-card {
    font-size: 0.875rem;
}

.very-small {
    font-size: 0.75rem;
}

.ads-container .ad-card:last-child {
    margin-bottom: 0;
}
</style>
