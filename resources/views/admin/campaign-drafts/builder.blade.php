@extends('layouts.admin')

@section('title', $draft->generated_name ?? 'Campaign Draft')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.campaign-drafts.index') }}">Campaign Drafts</a></li>
                    <li class="breadcrumb-item active">{{ $draft->generated_name ?? 'Draft' }}</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="h2 mb-2">{{ $draft->generated_name ?? 'Untitled Campaign Draft' }}</h1>
                    <span class="badge bg-{{ $draft->status === 'published' ? 'primary' : ($draft->status === 'approved' ? 'success' : 'secondary') }} fs-6">
                        {{ ucwords(str_replace('_', ' ', $draft->status)) }}
                    </span>
                </div>
                <div class="d-flex gap-2">
                    @if($validation['can_request_review'])
                        <form method="POST" action="{{ route('admin.campaign-drafts.request-review', $draft) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i> Request Review
                            </button>
                        </form>
                    @endif

                    @if($draft->status === 'ready_for_review')
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#requestApprovalModal">
                            <i class="bi bi-check-circle me-1"></i> Request Approval
                        </button>
                    @endif

                    @if($validation['can_publish'])
                        <form method="POST" action="{{ route('admin.campaign-drafts.publish', $draft) }}">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-rocket-takeoff me-1"></i> Publish Campaign
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('admin.campaign-drafts.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Tabs Navigation --}}
            <ul class="nav nav-tabs mb-4" id="draftTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                        <i class="bi bi-grid me-1"></i> Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="structure-tab" data-bs-toggle="tab" data-bs-target="#structure" type="button">
                        <i class="bi bi-diagram-3 me-1"></i> Structure
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button">
                        <i class="bi bi-textarea-t me-1"></i> Copy & Creatives
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ai-tab" data-bs-toggle="tab" data-bs-target="#ai" type="button">
                        <i class="bi bi-stars me-1"></i>
                        AI Enrichments
                        @if($enrichments['total_count'] > 0)
                            <span class="badge bg-warning ms-1">{{ $enrichments['total_count'] }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="review-tab" data-bs-toggle="tab" data-bs-target="#review" type="button">
                        <i class="bi bi-clipboard-check me-1"></i> Review & Publish
                    </button>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content" id="draftTabsContent">
                {{-- Overview Tab --}}
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    @if(count($validation['blockers'] ?? []) > 0)
                        <x-campaign-builder.validation-alerts :validation="$validation" />
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0">Briefing Details</h5>
                                </div>
                                <div class="card-body">
                                    @if($briefing)
                                        <div class="mb-3">
                                            <div class="small text-muted">Brand & Market</div>
                                            <div class="fw-bold">{{ $briefing->brand }} • {{ $briefing->market }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Objective</div>
                                            <span class="badge bg-primary">{{ $briefing->objective }}</span>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Product</div>
                                            <div>{{ $briefing->product_name }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Target Audience</div>
                                            <div class="small">{{ Str::limit($briefing->target_audience, 150) }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Campaign Goal</div>
                                            <div class="small">{{ Str::limit($briefing->campaign_goal, 150) }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Budget</div>
                                            <div class="fw-bold text-success">€{{ number_format($briefing->budget_amount, 2) }}</div>
                                        </div>
                                        <div>
                                            <div class="small text-muted">Landing Page</div>
                                            <a href="{{ $briefing->landing_page_url }}" target="_blank" class="small">
                                                {{ Str::limit($briefing->landing_page_url, 50) }}
                                                <i class="bi bi-box-arrow-up-right ms-1"></i>
                                            </a>
                                        </div>
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            No briefing linked to this draft
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <x-campaign-builder.readiness-card :readiness="$readiness" />
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Template & Strategy</h5>
                        </div>
                        <div class="card-body">
                            @if($template)
                                <div class="mb-3">
                                    <div class="small text-muted">Campaign Template</div>
                                    <div class="fw-bold">{{ $template->name }}</div>
                                    <div class="mt-2">
                                        <span class="badge bg-info me-1">{{ $template->funnel_stage }}</span>
                                        <span class="badge bg-secondary">{{ $template->objective }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Manual campaign configuration (no template used)
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex gap-2 flex-wrap">
                                @can('generate_ai_copy')
                                    <form method="POST" action="{{ route('admin.campaign-drafts.ai.generate-copy', $draft) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="bi bi-textarea-t me-1"></i> Generate Copy
                                        </button>
                                    </form>
                                @endcan

                                @can('generate_ai_creatives')
                                    <form method="POST" action="{{ route('admin.campaign-drafts.ai.generate-creative', $draft) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="bi bi-image me-1"></i> Generate Creative Ideas
                                        </button>
                                    </form>
                                @endcan

                                @can('generate_ai_copy')
                                    <form method="POST" action="{{ route('admin.campaign-drafts.ai.generate-full', $draft) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-stars me-1"></i> Full AI Package
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Structure Tab --}}
                <div class="tab-pane fade" id="structure" role="tabpanel">
                    <div class="mb-4">
                        <h4>Campaign Structure</h4>
                        <p class="text-muted">Visual overview of your campaign hierarchy</p>
                    </div>

                    <x-campaign-builder.campaign-tree :structure="$structure" />
                </div>

                {{-- Content Tab --}}
                <div class="tab-pane fade" id="content" role="tabpanel">
                    <div class="mb-4">
                        <h4>Copy & Creative Content</h4>
                        <p class="text-muted">Review and manage ad copy and creative elements</p>
                    </div>

                    @php
                        $ads = $structure['ads'] ?? [];
                    @endphp

                    @if(count($ads) > 0)
                        @foreach($ads as $ad)
                            <div class="card mb-4">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">{{ $ad['name'] }}</h5>
                                        <span class="badge bg-{{ $ad['completeness'] >= 75 ? 'success' : ($ad['completeness'] >= 50 ? 'warning' : 'danger') }}">
                                            {{ $ad['completeness'] }}% Complete
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold small text-muted">PRIMARY TEXT</label>
                                                <div class="p-3 bg-light rounded">
                                                    {{ $ad['primary_text'] ?? 'No primary text defined' }}
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold small text-muted">HEADLINE</label>
                                                <div class="p-3 bg-light rounded">
                                                    {{ $ad['headline'] ?? 'No headline defined' }}
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold small text-muted">DESCRIPTION</label>
                                                <div class="p-3 bg-light rounded">
                                                    {{ $ad['description'] ?? 'No description defined' }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold small text-muted">CALL TO ACTION</label>
                                                <div class="p-3 bg-light rounded">
                                                    @if($ad['cta'])
                                                        <span class="badge bg-primary">{{ $ad['cta'] }}</span>
                                                    @else
                                                        No CTA defined
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold small text-muted">LANDING PAGE</label>
                                                <div class="p-3 bg-light rounded">
                                                    @if($ad['link'])
                                                        <a href="{{ $ad['link'] }}" target="_blank" class="small">
                                                            {{ Str::limit($ad['link'], 60) }}
                                                            <i class="bi bi-box-arrow-up-right ms-1"></i>
                                                        </a>
                                                    @else
                                                        No link defined
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold small text-muted">STATUS</label>
                                                <div class="p-3 bg-light rounded">
                                                    <div class="d-flex gap-2 mb-2">
                                                        @if($ad['has_copy'])
                                                            <span class="badge bg-success"><i class="bi bi-check me-1"></i>Copy</span>
                                                        @else
                                                            <span class="badge bg-secondary">Missing Copy</span>
                                                        @endif

                                                        @if($ad['has_headline'])
                                                            <span class="badge bg-success"><i class="bi bi-check me-1"></i>Headline</span>
                                                        @else
                                                            <span class="badge bg-secondary">Missing Headline</span>
                                                        @endif

                                                        @if($ad['has_cta'])
                                                            <span class="badge bg-success"><i class="bi bi-check me-1"></i>CTA</span>
                                                        @else
                                                            <span class="badge bg-secondary">Missing CTA</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No ads defined in this campaign draft
                        </div>
                    @endif
                </div>

                {{-- AI Enrichments Tab --}}
                <div class="tab-pane fade" id="ai" role="tabpanel">
                    <div class="mb-4">
                        <h4>AI-Generated Content</h4>
                        <p class="text-muted">Review and apply AI suggestions to your campaign</p>
                    </div>

                    @if($enrichments['latest']->count() > 0)
                        @foreach($enrichments['latest'] as $enrichment)
                            <x-campaign-builder.ai-suggestion-card
                                :enrichment="$enrichment"
                                :type="$enrichment->enrichment_type === 'COPY_VARIANTS' ? 'copy' : ($enrichment->enrichment_type === 'CREATIVE_SUGGESTIONS' ? 'creative_suggestion' : 'creative')"
                            />
                        @endforeach

                        @if($enrichments['total_count'] > 3)
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Showing 3 of {{ $enrichments['total_count'] }} enrichments.
                                <a href="#" class="alert-link">View all</a>
                            </div>
                        @endif
                    @else
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-stars text-muted display-1 mb-3"></i>
                                <h5>No AI Enrichments Yet</h5>
                                <p class="text-muted">Generate AI-powered copy and creative suggestions to enhance your campaign</p>
                                <div class="d-flex gap-2 justify-content-center mt-4">
                                    @can('generate_ai_copy')
                                        <form method="POST" action="{{ route('admin.campaign-drafts.ai.generate-copy', $draft) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-textarea-t me-1"></i> Generate Copy
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.campaign-drafts.ai.generate-creative', $draft) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-primary">
                                                <i class="bi bi-image me-1"></i> Generate Creative Ideas
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Review & Publish Tab --}}
                <div class="tab-pane fade" id="review" role="tabpanel">
                    <div class="mb-4">
                        <h4>Review & Publish Readiness</h4>
                        <p class="text-muted">Final check before requesting review or publishing</p>
                    </div>

                    {{-- Validation Status --}}
                    <x-campaign-builder.validation-alerts :validation="$validation" />

                    {{-- Readiness --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <x-campaign-builder.readiness-card :readiness="$readiness" />
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Publish Checklist</h6>
                                </div>
                                <div class="card-body">
                                    @foreach($readiness['checks'] as $check)
                                        <div class="d-flex align-items-center mb-2">
                                            @if($check['passed'])
                                                <i class="bi bi-check-circle-fill text-success me-2"></i>
                                            @else
                                                <i class="bi bi-circle text-muted me-2"></i>
                                            @endif
                                            <span class="{{ $check['passed'] ? 'text-muted' : '' }}">
                                                {{ $check['name'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Campaign Summary --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Campaign Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="small text-muted">Campaign Name</div>
                                    <div class="fw-bold">{{ $draft->generated_name }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="small text-muted">Status</div>
                                    <span class="badge bg-{{ $draft->status === 'published' ? 'primary' : ($draft->status === 'approved' ? 'success' : 'secondary') }}">
                                        {{ ucwords(str_replace('_', ' ', $draft->status)) }}
                                    </span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="small text-muted">Ad Sets</div>
                                    <div class="fw-bold">{{ $structure['summary']['ad_set_count'] ?? 0 }}</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="small text-muted">Ads</div>
                                    <div class="fw-bold">{{ $structure['summary']['ad_count'] ?? 0 }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="card">
                        <div class="card-body">
                            <h6 class="mb-3">Ready to proceed?</h6>
                            <div class="d-flex gap-2">
                                @if($validation['can_request_review'])
                                    <form method="POST" action="{{ route('admin.campaign-drafts.request-review', $draft) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send me-1"></i> Submit for Review
                                        </button>
                                    </form>
                                @endif

                                @if($validation['can_publish'])
                                    <form method="POST" action="{{ route('admin.campaign-drafts.publish', $draft) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-rocket-takeoff me-1"></i> Publish to Meta
                                        </button>
                                    </form>
                                @endif

                                @if($validation['has_blockers'])
                                    <div class="alert alert-warning mb-0">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Please resolve all blockers before publishing
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sticky Sidebar --}}
        <div class="col-lg-4">
            <x-campaign-builder.sticky-sidebar
                :draft="$draft"
                :readiness="$readiness"
                :validation="$validation"
                :structure="$structure"
                :enrichments="$enrichments"
            />
        </div>
    </div>
</div>

{{-- Request Approval Modal --}}
@if($draft->status === 'ready_for_review')
<div class="modal fade" id="requestApprovalModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.campaign-drafts.request-approval', $draft) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Approval</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea name="notes" id="notes" rows="3" class="form-control"
                                  placeholder="Add any notes for the reviewer..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Request Approval
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
