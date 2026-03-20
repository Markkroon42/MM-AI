@extends('layouts.admin')

@section('title', 'AI Prompt Config')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">{{ $config->name }}</h1>
    <div>
        <a href="{{ route('admin.ai-prompt-configs.edit', $config) }}" class="btn btn-primary">Edit</a>
        <a href="{{ route('admin.ai-prompt-configs.index') }}" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Configuration</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Key</dt>
                    <dd class="col-sm-7"><code>{{ $config->key }}</code></dd>

                    <dt class="col-sm-5">Agent Type</dt>
                    <dd class="col-sm-7"><span class="badge bg-info">{{ $config->agent_type }}</span></dd>

                    <dt class="col-sm-5">Model</dt>
                    <dd class="col-sm-7">{{ $config->model }}</dd>

                    <dt class="col-sm-5">Temperature</dt>
                    <dd class="col-sm-7">{{ $config->temperature }}</dd>

                    <dt class="col-sm-5">Max Tokens</dt>
                    <dd class="col-sm-7">{{ number_format($config->max_tokens) }}</dd>

                    <dt class="col-sm-5">Status</dt>
                    <dd class="col-sm-7">
                        @if($config->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">System Prompt</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded"><code>{{ $config->system_prompt }}</code></pre>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">User Prompt Template</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded"><code>{{ $config->user_prompt_template }}</code></pre>
            </div>
        </div>

        @if($config->response_format)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Response Format</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded"><code>{{ json_encode($config->response_format, JSON_PRETTY_PRINT) }}</code></pre>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
