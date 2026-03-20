@extends('layouts.admin')

@section('title', 'Create Template')

@section('content')
<h1 class="h2 mb-4">Create Campaign Template</h1>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.campaign-templates.store') }}">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Brand *</label>
                    <input type="text" name="brand" class="form-control" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Market *</label>
                    <input type="text" name="market" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Objective *</label>
                    <input type="text" name="objective" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Funnel Stage *</label>
                    <input type="text" name="funnel_stage" class="form-control" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Default Budget ($) *</label>
                    <input type="number" step="0.01" name="default_budget" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">UTM Template</label>
                    <select name="default_utm_template_id" class="form-select">
                        <option value="">None</option>
                        @foreach($utmTemplates as $utm)
                            <option value="{{ $utm->id }}">{{ $utm->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Structure JSON *</label>
                <textarea name="structure_json" class="form-control" rows="8" required>{}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Creative Rules JSON *</label>
                <textarea name="creative_rules_json" class="form-control" rows="6" required>{}</textarea>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                    <label class="form-check-label">Active</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Create Template</button>
            <a href="{{ route('admin.campaign-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
