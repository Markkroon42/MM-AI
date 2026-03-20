@extends('layouts.admin')

@section('title', 'Campaign Builder - Briefing')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1">Campaign Builder</h1>
            <p class="text-muted mb-0">Create a new campaign briefing</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.campaign-briefings.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Briefings
            </a>
        </div>
    </div>

    {{-- Wizard Stepper --}}
    <x-campaign-builder.wizard-stepper
        :currentStep="1"
        :steps="[
            ['title' => 'Briefing', 'subtitle' => 'Define goals'],
            ['title' => 'Template', 'subtitle' => 'Choose structure'],
            ['title' => 'Generate', 'subtitle' => 'Create draft'],
        ]"
    />

    <form method="POST" action="{{ route('admin.campaign-briefings.store') }}" id="briefingForm">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                {{-- Basis Section --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Basic Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="brand" class="form-label">Brand *</label>
                                <input type="text" name="brand" id="brand"
                                       class="form-control @error('brand') is-invalid @enderror"
                                       value="{{ old('brand') }}"
                                       placeholder="e.g., Nike, Coolblue"
                                       required>
                                @error('brand')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="market" class="form-label">Market *</label>
                                <input type="text" name="market" id="market"
                                       class="form-control @error('market') is-invalid @enderror"
                                       value="{{ old('market') }}"
                                       placeholder="e.g., Netherlands, Belgium"
                                       required>
                                @error('market')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="objective" class="form-label">Campaign Objective *</label>
                                <select name="objective" id="objective"
                                        class="form-select @error('objective') is-invalid @enderror"
                                        required>
                                    <option value="">Select objective...</option>
                                    <option value="CONVERSIONS" {{ old('objective') === 'CONVERSIONS' ? 'selected' : '' }}>Conversions</option>
                                    <option value="TRAFFIC" {{ old('objective') === 'TRAFFIC' ? 'selected' : '' }}>Traffic</option>
                                    <option value="AWARENESS" {{ old('objective') === 'AWARENESS' ? 'selected' : '' }}>Awareness</option>
                                    <option value="ENGAGEMENT" {{ old('objective') === 'ENGAGEMENT' ? 'selected' : '' }}>Engagement</option>
                                    <option value="LEADS" {{ old('objective') === 'LEADS' ? 'selected' : '' }}>Leads</option>
                                </select>
                                @error('objective')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="product_name" class="form-label">Product Name *</label>
                                <input type="text" name="product_name" id="product_name"
                                       class="form-control @error('product_name') is-invalid @enderror"
                                       value="{{ old('product_name') }}"
                                       placeholder="e.g., Summer Sale 2026"
                                       required>
                                @error('product_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Audience & Goal Section --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>
                            Target Audience & Goals
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="target_audience" class="form-label">Target Audience *</label>
                            <textarea name="target_audience" id="target_audience" rows="4"
                                      class="form-control @error('target_audience') is-invalid @enderror"
                                      placeholder="Describe your target audience: demographics, interests, behaviors..."
                                      required>{{ old('target_audience') }}</textarea>
                            <div class="form-text">
                                Example: Men 25-45 years, interested in fitness and sports, living in Netherlands, mid-high income
                            </div>
                            @error('target_audience')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="campaign_goal" class="form-label">Campaign Goal *</label>
                            <textarea name="campaign_goal" id="campaign_goal" rows="4"
                                      class="form-control @error('campaign_goal') is-invalid @enderror"
                                      placeholder="What do you want to achieve with this campaign?"
                                      required>{{ old('campaign_goal') }}</textarea>
                            <div class="form-text">
                                Example: Generate 500 online purchases with a target ROAS of 3.0 within 30 days
                            </div>
                            @error('campaign_goal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Budget & Landing Page Section --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-currency-euro me-2"></i>
                            Budget & Landing Page
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="budget_amount" class="form-label">Budget Amount (€) *</label>
                                <input type="number" step="0.01" min="0" name="budget_amount" id="budget_amount"
                                       class="form-control @error('budget_amount') is-invalid @enderror"
                                       value="{{ old('budget_amount') }}"
                                       placeholder="5000.00"
                                       required>
                                @error('budget_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-8">
                                <label for="landing_page_url" class="form-label">Landing Page URL *</label>
                                <input type="url" name="landing_page_url" id="landing_page_url"
                                       class="form-control @error('landing_page_url') is-invalid @enderror"
                                       value="{{ old('landing_page_url') }}"
                                       placeholder="https://www.example.com/products/summer-sale"
                                       required>
                                @error('landing_page_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Notes Section --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-sticky me-2"></i>
                            Additional Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-0">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      placeholder="Any additional context, constraints, or specific requirements...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex justify-content-between mb-4">
                    <a href="{{ route('admin.campaign-briefings.index') }}" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check2 me-2"></i>
                        Create Briefing & Continue
                    </button>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="col-lg-4">
                <div class="sticky-sidebar">
                    <div class="card mb-3">
                        <div class="card-header bg-primary bg-opacity-10">
                            <h6 class="mb-0">
                                <i class="bi bi-lightbulb me-2"></i>
                                Quick Tips
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 ps-3">
                                <li class="mb-2">Be specific about your target audience</li>
                                <li class="mb-2">Define clear, measurable goals</li>
                                <li class="mb-2">Ensure landing page is ready</li>
                                <li class="mb-0">Start with a conservative budget</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">Next Steps</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex mb-3">
                                <div class="me-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                         style="width: 32px; height: 32px; font-weight: 600;">
                                        1
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">Create Briefing</div>
                                    <div class="small text-muted">Define campaign goals</div>
                                </div>
                            </div>
                            <div class="d-flex mb-3">
                                <div class="me-3">
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                         style="width: 32px; height: 32px; font-weight: 600;">
                                        2
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">Select Template</div>
                                    <div class="small text-muted">Choose campaign structure</div>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="me-3">
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                         style="width: 32px; height: 32px; font-weight: 600;">
                                        3
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold">Generate Draft</div>
                                    <div class="small text-muted">AI creates campaign</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-stars text-warning display-4 mb-2"></i>
                            <h6>AI-Powered Generation</h6>
                            <p class="small text-muted mb-0">
                                After creating the briefing, AI will generate campaign structure, targeting, and copy suggestions
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.sticky-sidebar {
    position: sticky;
    top: 20px;
}
</style>
@endsection
