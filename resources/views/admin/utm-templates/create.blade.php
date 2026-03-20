@extends('layouts.admin')

@section('title', 'Create UTM Template')

@section('content')
<h1 class="h2 mb-4">Create UTM Template</h1>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.utm-templates.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Source *</label>
                    <input type="text" name="source" class="form-control" placeholder="e.g., facebook, google" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Medium *</label>
                    <input type="text" name="medium" class="form-control" placeholder="e.g., cpc, social" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Campaign Pattern *</label>
                <input type="text" name="campaign_pattern" class="form-control" placeholder="e.g., {BRAND}_{MARKET}_{YYYYMM}" required>
                <div class="form-text">Available variables: {BRAND}, {MARKET}, {CAMPAIGN_NAME}, {OBJECTIVE}, {YYYYMM}, {YYYY}, {MM}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Content Pattern</label>
                <input type="text" name="content_pattern" class="form-control" placeholder="Optional">
            </div>
            <div class="mb-3">
                <label class="form-label">Term Pattern</label>
                <input type="text" name="term_pattern" class="form-control" placeholder="Optional">
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                    <label class="form-check-label">Active</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Create Template</button>
            <a href="{{ route('admin.utm-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
