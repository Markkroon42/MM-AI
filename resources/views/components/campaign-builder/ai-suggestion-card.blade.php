@props(['enrichment', 'type' => 'copy'])

@php
    $content = is_array($enrichment->payload_json ?? $enrichment['payload_json'] ?? null)
        ? ($enrichment->payload_json ?? $enrichment['payload_json'])
        : json_decode($enrichment['enrichment_payload'] ?? '{}', true);
@endphp

<div class="ai-suggestion-card card mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-stars text-warning me-2"></i>
            <strong>{{ ucfirst($type) }} Suggestion</strong>
        </div>
        <div class="text-muted small">
            {{ is_object($enrichment) ? $enrichment->created_at->format('M d, Y') : \Carbon\Carbon::parse($enrichment['created_at'])->format('M d, Y') }}
        </div>
    </div>
    <div class="card-body">
        @if($type === 'copy')
            @if(!empty($content['primary_text']))
                <div class="mb-3">
                    <div class="small text-muted fw-bold mb-1">Primary Text</div>
                    <div class="suggestion-content">{{ $content['primary_text'] }}</div>
                </div>
            @endif

            @if(!empty($content['headline']))
                <div class="mb-3">
                    <div class="small text-muted fw-bold mb-1">Headline</div>
                    <div class="suggestion-content">{{ $content['headline'] }}</div>
                </div>
            @endif

            @if(!empty($content['description']))
                <div class="mb-3">
                    <div class="small text-muted fw-bold mb-1">Description</div>
                    <div class="suggestion-content">{{ $content['description'] }}</div>
                </div>
            @endif
        @elseif($type === 'creative')
            @if(!empty($content['concept']))
                <div class="mb-3">
                    <div class="small text-muted fw-bold mb-1">Concept</div>
                    <div class="fw-bold">{{ $content['concept'] }}</div>
                </div>
            @endif

            @if(!empty($content['format']))
                <div class="mb-2">
                    <span class="badge bg-primary">{{ $content['format'] }}</span>
                </div>
            @endif

            @if(!empty($content['description']))
                <div class="mb-3">
                    <div class="suggestion-content">{{ $content['description'] }}</div>
                </div>
            @endif

            @if(!empty($content['hook']))
                <div class="mb-2">
                    <div class="small text-muted fw-bold mb-1">Hook</div>
                    <div class="suggestion-content">{{ $content['hook'] }}</div>
                </div>
            @endif
        @endif
    </div>
    <div class="card-footer bg-transparent">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary flex-grow-1">
                <i class="bi bi-check2 me-1"></i>
                Use This
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-clipboard-plus"></i>
            </button>
        </div>
    </div>
</div>

<style>
.ai-suggestion-card {
    border: 2px solid #ffc107;
    background: linear-gradient(135deg, #fff9e6 0%, #ffffff 100%);
}

.suggestion-content {
    background-color: white;
    padding: 0.75rem;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}
</style>
