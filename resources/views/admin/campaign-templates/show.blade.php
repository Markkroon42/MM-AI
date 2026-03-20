@extends('layouts.admin')

@section('title', 'Template Details')

@section('content')
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.campaign-templates.index') }}">Templates</a></li>
        <li class="breadcrumb-item active">{{ $template->name }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">{{ $template->name }}</h1>
    <a href="{{ route('admin.campaign-templates.edit', $template) }}" class="btn btn-primary">Edit</a>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Template Information</h5></div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6"><strong>Brand:</strong> {{ $template->brand }}</div>
            <div class="col-md-6"><strong>Market:</strong> {{ $template->market }}</div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6"><strong>Objective:</strong> {{ $template->objective }}</div>
            <div class="col-md-6"><strong>Funnel Stage:</strong> {{ $template->funnel_stage }}</div>
        </div>
        <div class="row">
            <div class="col-md-6"><strong>Default Budget:</strong> ${{ number_format($template->default_budget, 2) }}</div>
            <div class="col-md-6"><strong>UTM Template:</strong> {{ $template->utmTemplate?->name ?? 'None' }}</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Structure JSON</h5></div>
    <div class="card-body">
        <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($template->structure_json, JSON_PRETTY_PRINT) }}</code></pre>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Creative Rules JSON</h5></div>
    <div class="card-body">
        <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($template->creative_rules_json, JSON_PRETTY_PRINT) }}</code></pre>
    </div>
</div>
@endsection
