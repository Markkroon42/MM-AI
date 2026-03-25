@props(['enrichment', 'type' => 'copy'])

@php
    $content = is_array($enrichment->payload_json ?? $enrichment['payload_json'] ?? null)
        ? ($enrichment->payload_json ?? $enrichment['payload_json'])
        : json_decode($enrichment['enrichment_payload'] ?? '{}', true);

    // Log for debugging
    \Log::info('[DRAFT_ENRICHMENT_UI] Rendering AI suggestion card', [
        'enrichment_id' => is_object($enrichment) ? $enrichment->id : ($enrichment['id'] ?? null),
        'enrichment_type' => is_object($enrichment) ? $enrichment->enrichment_type : ($enrichment['enrichment_type'] ?? null),
        'type_prop' => $type,
        'has_content' => !empty($content),
        'content_keys' => !empty($content) ? array_keys($content) : [],
    ]);

    // Determine actual enrichment type
    $enrichmentType = is_object($enrichment) ? $enrichment->enrichment_type : ($enrichment['enrichment_type'] ?? 'unknown');
    $enrichmentId = is_object($enrichment) ? $enrichment->id : ($enrichment['id'] ?? null);
    $enrichmentStatus = is_object($enrichment) ? $enrichment->status : ($enrichment['status'] ?? 'draft');

    // Auto-detect type from enrichment_type if needed
    if ($enrichmentType === 'CREATIVE_SUGGESTIONS') {
        $type = 'creative_suggestion';
    } elseif ($enrichmentType === 'COPY_VARIANTS') {
        $type = 'copy';
    }
@endphp

<div class="ai-suggestion-card card mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-stars text-warning me-2"></i>
            <strong>{{ ucwords(str_replace('_', ' ', $enrichmentType)) }}</strong>
            @if($enrichmentStatus === 'applied')
                <span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Applied</span>
            @endif
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
        @elseif($type === 'creative_suggestion')
            @if(!empty($content['suggestions']) && is_array($content['suggestions']))
                @foreach($content['suggestions'] as $index => $suggestion)
                    <div class="suggestion-item mb-3 p-3 bg-white border rounded">
                        @if(!empty($suggestion['title']))
                            <h6 class="fw-bold mb-2">{{ $suggestion['title'] }}</h6>
                        @endif

                        @if(!empty($suggestion['hook']))
                            <div class="mb-2">
                                <div class="small text-muted fw-bold mb-1">Hook</div>
                                <div class="suggestion-content">{{ $suggestion['hook'] }}</div>
                            </div>
                        @endif

                        @if(!empty($suggestion['format']))
                            <div class="mb-2">
                                <span class="badge bg-primary">{{ $suggestion['format'] }}</span>
                            </div>
                        @endif

                        @if(!empty($suggestion['description']))
                            <div class="mb-2">
                                <div class="small text-muted fw-bold mb-1">Description</div>
                                <div class="suggestion-content">{{ $suggestion['description'] }}</div>
                            </div>
                        @endif

                        @if(!empty($suggestion['angle']))
                            <div class="mb-2">
                                <div class="small text-muted fw-bold mb-1">Angle</div>
                                <div class="suggestion-content">{{ $suggestion['angle'] }}</div>
                            </div>
                        @endif

                        @if(!empty($suggestion['cta_direction']))
                            <div class="mb-2">
                                <div class="small text-muted fw-bold mb-1">CTA Direction</div>
                                <div class="suggestion-content">{{ $suggestion['cta_direction'] }}</div>
                            </div>
                        @endif

                        @if(!empty($suggestion['target_audience_context']))
                            <div class="mb-2">
                                <div class="small text-muted fw-bold mb-1">Target Audience</div>
                                <div class="suggestion-content">{{ $suggestion['target_audience_context'] }}</div>
                            </div>
                        @endif

                        @if(!empty($suggestion['body']))
                            <div class="mb-2">
                                <div class="small text-muted fw-bold mb-1">Body</div>
                                <div class="suggestion-content">{{ $suggestion['body'] }}</div>
                            </div>
                        @endif

                        @if(!empty($suggestion['summary']))
                            <div class="mb-0">
                                <div class="small text-muted fw-bold mb-1">Summary</div>
                                <div class="suggestion-content">{{ $suggestion['summary'] }}</div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @elseif(!empty($content['static_visual_ideas']) || !empty($content['video_concepts']) || !empty($content['ugc_angles']))
                {{-- Legacy creative format --}}
                @if(!empty($content['static_visual_ideas']))
                    <div class="mb-3">
                        <div class="small text-muted fw-bold mb-1">Static Visual Ideas</div>
                        <ul class="mb-0">
                            @foreach($content['static_visual_ideas'] as $idea)
                                <li>{{ is_array($idea) ? json_encode($idea) : $idea }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($content['video_concepts']))
                    <div class="mb-3">
                        <div class="small text-muted fw-bold mb-1">Video Concepts</div>
                        <ul class="mb-0">
                            @foreach($content['video_concepts'] as $concept)
                                <li>{{ is_array($concept) ? json_encode($concept) : $concept }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!empty($content['ugc_angles']))
                    <div class="mb-3">
                        <div class="small text-muted fw-bold mb-1">UGC Angles</div>
                        <ul class="mb-0">
                            @foreach($content['ugc_angles'] as $angle)
                                <li>{{ is_array($angle) ? json_encode($angle) : $angle }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @else
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    No preview available for this creative suggestion format.
                </div>
            @endif
        @elseif($type === 'creative')
            {{-- Legacy 'creative' type --}}
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
        <div class="d-flex gap-2 align-items-center">
            @if($enrichmentStatus === 'draft' || $enrichmentStatus === 'approved')
                @can('apply_draft_enrichments')
                <form method="POST" action="{{ route('admin.draft-enrichments.apply', $enrichmentId) }}" class="flex-grow-1">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-check2 me-1"></i>
                        Use This
                    </button>
                </form>
                @endcan
            @elseif($enrichmentStatus === 'applied')
                <div class="text-success flex-grow-1 text-center">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Applied to draft
                </div>
            @endif

            @if($enrichmentStatus !== 'applied')
                @can('review_draft_enrichments')
                <form method="POST" action="{{ route('admin.draft-enrichments.reject', $enrichmentId) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Reject">
                        <i class="bi bi-x-circle"></i>
                    </button>
                </form>
                @endcan
            @endif
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
