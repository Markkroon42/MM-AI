@props(['readiness'])

@php
    $percentage = $readiness['percentage'] ?? 0;
    $level = $readiness['level'] ?? 'incomplete';
    $checks = $readiness['checks'] ?? [];
    $passedCount = $readiness['passed_count'] ?? 0;
    $totalCount = $readiness['total_count'] ?? 0;

    $levelConfig = [
        'ready' => ['color' => 'success', 'text' => 'Ready for Review'],
        'almost-ready' => ['color' => 'info', 'text' => 'Almost Ready'],
        'in-progress' => ['color' => 'warning', 'text' => 'In Progress'],
        'incomplete' => ['color' => 'danger', 'text' => 'Incomplete'],
    ];

    $config = $levelConfig[$level] ?? $levelConfig['incomplete'];
@endphp

<div class="card readiness-card border-{{ $config['color'] }}">
    <div class="card-header bg-{{ $config['color'] }} bg-opacity-10">
        <h6 class="mb-0 d-flex align-items-center">
            <i class="bi bi-speedometer2 me-2"></i>
            Campaign Readiness
        </h6>
    </div>
    <div class="card-body">
        <div class="text-center mb-3">
            <div class="readiness-percentage display-4 fw-bold text-{{ $config['color'] }}">
                {{ $percentage }}%
            </div>
            <div class="text-muted small">{{ $config['text'] }}</div>
        </div>

        <div class="progress mb-3" style="height: 8px;">
            <div class="progress-bar bg-{{ $config['color'] }}"
                 role="progressbar"
                 style="width: {{ $percentage }}%"
                 aria-valuenow="{{ $percentage }}"
                 aria-valuemin="0"
                 aria-valuemax="100">
            </div>
        </div>

        <div class="small text-muted mb-2">
            <strong>{{ $passedCount }}</strong> of <strong>{{ $totalCount }}</strong> checks passed
        </div>

        <div class="checklist">
            @foreach($checks as $check)
                <div class="checklist-item d-flex align-items-start mb-2">
                    @if($check['passed'])
                        <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                    @else
                        <i class="bi bi-circle text-muted me-2 mt-1"></i>
                    @endif
                    <span class="{{ $check['passed'] ? 'text-muted' : '' }}">
                        {{ $check['name'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
.readiness-card {
    border-width: 2px;
}

.checklist-item {
    font-size: 0.9rem;
}
</style>
