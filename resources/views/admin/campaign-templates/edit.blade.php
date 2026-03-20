@extends('layouts.admin')

@section('title', 'Edit Template')

@section('content')
<h1 class="h2 mb-4">Edit Campaign Template</h1>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.campaign-templates.update', $template) }}">
            @csrf
            @method('PUT')
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ $template->name }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Brand *</label>
                    <input type="text" name="brand" class="form-control" value="{{ $template->brand }}" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Market *</label>
                    <input type="text" name="market" class="form-control" value="{{ $template->market }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Objective *</label>
                    <input type="text" name="objective" class="form-control" value="{{ $template->objective }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Funnel Stage *</label>
                    <input type="text" name="funnel_stage" class="form-control" value="{{ $template->funnel_stage }}" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Default Budget ($) *</label>
                    <input type="number" step="0.01" name="default_budget" class="form-control" value="{{ $template->default_budget }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">UTM Template</label>
                    <select name="default_utm_template_id" class="form-select">
                        <option value="">None</option>
                        @foreach($utmTemplates as $utm)
                            <option value="{{ $utm->id }}" {{ $template->default_utm_template_id == $utm->id ? 'selected' : '' }}>{{ $utm->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Structure JSON *</label>
                <textarea name="structure_json" class="form-control" rows="8" required>{{ json_encode($template->structure_json) }}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Creative Rules JSON *</label>
                <textarea name="creative_rules_json" class="form-control" rows="6" required>{{ json_encode($template->creative_rules_json) }}</textarea>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $template->is_active ? 'checked' : '' }}>
                    <label class="form-check-label">Active</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Template</button>
            <a href="{{ route('admin.campaign-templates.show', $template) }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
