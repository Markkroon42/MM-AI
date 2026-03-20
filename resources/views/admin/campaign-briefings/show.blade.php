@extends('layouts.admin')

@section('title', 'Campaign Briefing Details')

@section('content')
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.campaign-briefings.index') }}">Campaign Briefings</a></li>
        <li class="breadcrumb-item active">{{ $briefing->brand }} - {{ $briefing->market }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Campaign Briefing Details</h1>
</div>

<!-- Details Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Briefing Information</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Brand:</strong> {{ $briefing->brand }}
            </div>
            <div class="col-md-6">
                <strong>Market:</strong> {{ $briefing->market }}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Objective:</strong> {{ $briefing->objective }}
            </div>
            <div class="col-md-6">
                <strong>Product Name:</strong> {{ $briefing->product_name }}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Budget Amount:</strong> ${{ number_format($briefing->budget_amount, 2) }}
            </div>
            <div class="col-md-6">
                <strong>Status:</strong>
                <span class="badge {{ $briefing->status === 'draft' ? 'bg-secondary' : ($briefing->status === 'generated' ? 'bg-success' : 'bg-info') }}">
                    {{ ucwords(str_replace('_', ' ', $briefing->status)) }}
                </span>
            </div>
        </div>
        <div class="mb-3">
            <strong>Target Audience:</strong>
            <p class="mt-2">{{ $briefing->target_audience }}</p>
        </div>
        <div class="mb-3">
            <strong>Landing Page URL:</strong>
            <p class="mt-2"><a href="{{ $briefing->landing_page_url }}" target="_blank">{{ $briefing->landing_page_url }}</a></p>
        </div>
        <div class="mb-3">
            <strong>Campaign Goal:</strong>
            <p class="mt-2">{{ $briefing->campaign_goal }}</p>
        </div>
        @if($briefing->notes)
            <div class="mb-3">
                <strong>Notes:</strong>
                <p class="mt-2">{{ $briefing->notes }}</p>
            </div>
        @endif
    </div>
</div>

<!-- Generate Draft Section -->
@if($briefing->status !== 'generated')
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Generate Campaign Draft</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.campaign-briefings.generate-draft', $briefing) }}">
            @csrf
            <div class="mb-3">
                <label for="template_id" class="form-label">Select Template</label>
                <select name="template_id" id="template_id" class="form-select" required>
                    <option value="">-- Select a Template --</option>
                    @foreach(\App\Models\CampaignTemplate::where('is_active', true)->get() as $template)
                        <option value="{{ $template->id }}">
                            {{ $template->name }} ({{ $template->brand }} - {{ $template->market }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Generate Draft</button>
        </form>
    </div>
</div>
@endif

<!-- Related Drafts -->
@if($briefing->campaignDrafts->count() > 0)
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Generated Drafts</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($briefing->campaignDrafts as $draft)
                        <tr>
                            <td>{{ $draft->generated_name }}</td>
                            <td><span class="badge bg-secondary">{{ $draft->status }}</span></td>
                            <td>{{ $draft->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.campaign-drafts.show', $draft) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
