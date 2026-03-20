@props(['draft', 'readiness', 'validation', 'structure', 'enrichments' => []])

<div class="sticky-sidebar">
    {{-- Campaign Summary --}}
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0">Campaign Summary</h6>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div class="small text-muted">Campaign Name</div>
                <div class="fw-bold">{{ $draft->generated_name ?? 'Untitled' }}</div>
            </div>

            @if($draft->briefing)
                <div class="mb-3">
                    <div class="small text-muted">Brand • Market</div>
                    <div>{{ $draft->briefing->brand }} • {{ $draft->briefing->market }}</div>
                </div>

                <div class="mb-3">
                    <div class="small text-muted">Objective</div>
                    <div><span class="badge bg-primary">{{ $draft->briefing->objective }}</span></div>
                </div>

                @if($draft->briefing->budget_amount)
                    <div class="mb-3">
                        <div class="small text-muted">Budget</div>
                        <div class="fw-bold text-success">€{{ number_format($draft->briefing->budget_amount, 2) }}</div>
                    </div>
                @endif
            @endif

            @if($draft->template)
                <div class="mb-3">
                    <div class="small text-muted">Template</div>
                    <div>{{ $draft->template->name }}</div>
                </div>
            @endif

            <div class="mb-0">
                <div class="small text-muted">Status</div>
                <span class="badge bg-{{ $draft->status === 'published' ? 'primary' : ($draft->status === 'approved' ? 'success' : 'secondary') }}">
                    {{ ucwords(str_replace('_', ' ', $draft->status)) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Structure Summary --}}
    @if(isset($structure['summary']))
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">Structure</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Ad Sets</span>
                    <strong>{{ $structure['summary']['ad_set_count'] ?? 0 }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-0">
                    <span class="text-muted">Ads</span>
                    <strong>{{ $structure['summary']['ad_count'] ?? 0 }}</strong>
                </div>
            </div>
        </div>
    @endif

    {{-- Readiness Compact --}}
    <div class="card mb-3 border-{{ $readiness['level'] === 'ready' ? 'success' : ($readiness['level'] === 'almost-ready' ? 'info' : 'warning') }}">
        <div class="card-header bg-{{ $readiness['level'] === 'ready' ? 'success' : ($readiness['level'] === 'almost-ready' ? 'info' : 'warning') }} bg-opacity-10">
            <h6 class="mb-0">Readiness</h6>
        </div>
        <div class="card-body">
            <div class="text-center mb-2">
                <div class="display-6 fw-bold text-{{ $readiness['level'] === 'ready' ? 'success' : ($readiness['level'] === 'almost-ready' ? 'info' : 'warning') }}">
                    {{ $readiness['percentage'] }}%
                </div>
            </div>
            <div class="progress mb-2" style="height: 6px;">
                <div class="progress-bar bg-{{ $readiness['level'] === 'ready' ? 'success' : ($readiness['level'] === 'almost-ready' ? 'info' : 'warning') }}"
                     style="width: {{ $readiness['percentage'] }}%">
                </div>
            </div>
            <div class="small text-center text-muted">
                {{ $readiness['passed_count'] }} of {{ $readiness['total_count'] }} checks
            </div>
        </div>
    </div>

    {{-- Top Warnings --}}
    @if(count($validation['warnings'] ?? []) > 0 || count($validation['blockers'] ?? []) > 0)
        <div class="card mb-3">
            <div class="card-header bg-warning bg-opacity-10">
                <h6 class="mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Attention Needed
                </h6>
            </div>
            <div class="card-body">
                @foreach(array_slice($validation['blockers'] ?? [], 0, 3) as $blocker)
                    <div class="small text-danger mb-2">
                        <i class="bi bi-x-circle-fill me-1"></i>
                        {{ $blocker['message'] }}
                    </div>
                @endforeach
                @foreach(array_slice($validation['warnings'] ?? [], 0, 3) as $warning)
                    <div class="small text-warning mb-2">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                        {{ $warning['message'] }}
                    </div>
                @endforeach

                @if(count($validation['warnings'] ?? []) + count($validation['blockers'] ?? []) > 3)
                    <div class="small text-muted mt-2">
                        +{{ count($validation['warnings'] ?? []) + count($validation['blockers'] ?? []) - 3 }} more issues
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- AI Summary --}}
    @if(!empty($enrichments['total_count']) && $enrichments['total_count'] > 0)
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="bi bi-stars me-1"></i>
                    AI Enrichments
                </h6>
            </div>
            <div class="card-body">
                @if($enrichments['copy_count'] > 0)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Copy Variants</span>
                        <strong>{{ $enrichments['copy_count'] }}</strong>
                    </div>
                @endif
                @if($enrichments['creative_count'] > 0)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Creative Ideas</span>
                        <strong>{{ $enrichments['creative_count'] }}</strong>
                    </div>
                @endif
                @if($enrichments['strategy_count'] > 0)
                    <div class="d-flex justify-content-between mb-0">
                        <span class="text-muted small">Strategy Notes</span>
                        <strong>{{ $enrichments['strategy_count'] }}</strong>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Last Updated --}}
    <div class="card">
        <div class="card-body">
            <div class="small text-muted">Last updated</div>
            <div class="small">{{ $draft->updated_at->diffForHumans() }}</div>
        </div>
    </div>
</div>

<style>
.sticky-sidebar {
    position: sticky;
    top: 20px;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
}

.sticky-sidebar .card {
    font-size: 0.9rem;
}

.sticky-sidebar .card-header h6 {
    font-size: 0.9rem;
}
</style>
