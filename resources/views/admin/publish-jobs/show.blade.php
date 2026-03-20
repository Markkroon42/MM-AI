@extends('layouts.admin')

@section('title', 'Publish Job Details')

@section('content')
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.publish-jobs.index') }}">Publish Jobs</a></li>
        <li class="breadcrumb-item active">Job #{{ $job->id }}</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Publish Job #{{ $job->id }}</h1>
    @if($job->status === 'failed')
        <form method="POST" action="{{ route('admin.publish-jobs.retry', $job) }}">
            @csrf
            <button type="submit" class="btn btn-warning">Retry Job</button>
        </form>
    @endif
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Job Information</h5></div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6"><strong>Provider:</strong> {{ $job->provider }}</div>
            <div class="col-md-6"><strong>Action Type:</strong> {{ ucwords(str_replace('_', ' ', $job->action_type)) }}</div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6"><strong>Status:</strong> <span class="badge bg-{{ $job->status === 'success' ? 'success' : ($job->status === 'failed' ? 'danger' : 'warning') }}">{{ $job->status }}</span></div>
            <div class="col-md-6"><strong>Attempts:</strong> {{ $job->attempts }}</div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6"><strong>Executed At:</strong> {{ $job->executed_at?->format('Y-m-d H:i:s') ?? '-' }}</div>
            @if($job->draft)
                <div class="col-md-6"><strong>Draft:</strong> <a href="{{ route('admin.campaign-drafts.show', $job->draft) }}">{{ $job->draft->generated_name }}</a></div>
            @endif
        </div>
        @if($job->error_message)
            <div class="alert alert-danger mt-3">
                <strong>Error Message:</strong><br>
                {{ $job->error_message }}
            </div>
        @endif
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Payload</h5></div>
    <div class="card-body">
        <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($job->payload_json, JSON_PRETTY_PRINT) }}</code></pre>
    </div>
</div>

@if($job->response_json)
<div class="card">
    <div class="card-header"><h5 class="mb-0">Response</h5></div>
    <div class="card-body">
        <pre class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($job->response_json, JSON_PRETTY_PRINT) }}</code></pre>
    </div>
</div>
@endif
@endsection
