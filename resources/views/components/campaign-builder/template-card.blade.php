@props(['template', 'selected' => false])

<div class="template-card card h-100 {{ $selected ? 'border-primary border-2' : '' }}" style="cursor: pointer;">
    <div class="card-body">
        @if($selected)
            <div class="position-absolute top-0 end-0 m-2">
                <i class="bi bi-check-circle-fill text-primary fs-4"></i>
            </div>
        @endif

        <h5 class="card-title mb-3">{{ $template['name'] }}</h5>

        <div class="mb-2">
            <span class="badge bg-primary me-1">{{ $template['brand'] ?? 'Any Brand' }}</span>
            <span class="badge bg-info me-1">{{ $template['market'] ?? 'Any Market' }}</span>
        </div>

        <div class="mb-3">
            <div class="small text-muted">Objective</div>
            <div><strong>{{ $template['objective'] ?? 'N/A' }}</strong></div>
        </div>

        <div class="mb-3">
            <div class="small text-muted">Funnel Stage</div>
            <div><span class="badge bg-secondary">{{ $template['funnel_stage'] ?? 'N/A' }}</span></div>
        </div>

        @if(!empty($template['default_budget']))
            <div class="mb-3">
                <div class="small text-muted">Default Budget</div>
                <div class="fw-bold text-success">€{{ number_format($template['default_budget'], 2) }}</div>
            </div>
        @endif

        <div class="small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Pre-configured structure with targeting, copy templates, and creative guidelines
        </div>
    </div>

    @if(!$selected)
        <div class="card-footer bg-transparent">
            <button type="button" class="btn btn-outline-primary btn-sm w-100">
                Select Template
            </button>
        </div>
    @else
        <div class="card-footer bg-primary bg-opacity-10">
            <div class="text-primary fw-bold small text-center">
                <i class="bi bi-check-circle-fill me-1"></i>
                Selected
            </div>
        </div>
    @endif
</div>

<style>
.template-card {
    transition: all 0.3s ease;
}

.template-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.template-card.border-2 {
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}
</style>
