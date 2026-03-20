@extends('layouts.admin')

@section('title', 'Create Campaign Briefing')

@section('content')
<div class="mb-4">
    <h1 class="h2">Create Campaign Briefing</h1>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.campaign-briefings.store') }}">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="brand" class="form-label">Brand *</label>
                    <input type="text" name="brand" id="brand" class="form-control @error('brand') is-invalid @enderror" value="{{ old('brand') }}" required>
                    @error('brand')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="market" class="form-label">Market *</label>
                    <input type="text" name="market" id="market" class="form-control @error('market') is-invalid @enderror" value="{{ old('market') }}" required>
                    @error('market')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="objective" class="form-label">Objective *</label>
                    <input type="text" name="objective" id="objective" class="form-control @error('objective') is-invalid @enderror" value="{{ old('objective') }}" required>
                    @error('objective')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="product_name" class="form-label">Product Name *</label>
                    <input type="text" name="product_name" id="product_name" class="form-control @error('product_name') is-invalid @enderror" value="{{ old('product_name') }}" required>
                    @error('product_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="target_audience" class="form-label">Target Audience *</label>
                <textarea name="target_audience" id="target_audience" rows="3" class="form-control @error('target_audience') is-invalid @enderror" required>{{ old('target_audience') }}</textarea>
                @error('target_audience')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label for="landing_page_url" class="form-label">Landing Page URL *</label>
                    <input type="url" name="landing_page_url" id="landing_page_url" class="form-control @error('landing_page_url') is-invalid @enderror" value="{{ old('landing_page_url') }}" required>
                    @error('landing_page_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="budget_amount" class="form-label">Budget Amount ($) *</label>
                    <input type="number" step="0.01" name="budget_amount" id="budget_amount" class="form-control @error('budget_amount') is-invalid @enderror" value="{{ old('budget_amount') }}" required>
                    @error('budget_amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="campaign_goal" class="form-label">Campaign Goal *</label>
                <textarea name="campaign_goal" id="campaign_goal" rows="3" class="form-control @error('campaign_goal') is-invalid @enderror" required>{{ old('campaign_goal') }}</textarea>
                @error('campaign_goal')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.campaign-briefings.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Briefing</button>
            </div>
        </form>
    </div>
</div>
@endsection
