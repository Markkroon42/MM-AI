@extends('layouts.admin')

@section('title', 'AI Usage Log')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">AI Usage Log #{{ $log->id }}</h1>
    <a href="{{ route('admin.ai-usage-logs.index') }}" class="btn btn-secondary">Back</a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Agent</dt>
                    <dd class="col-sm-7"><span class="badge bg-primary">{{ $log->agent_name }}</span></dd>

                    <dt class="col-sm-5">Model</dt>
                    <dd class="col-sm-7">{{ $log->model }}</dd>

                    <dt class="col-sm-5">Status</dt>
                    <dd class="col-sm-7">
                        @if($log->status == 'SUCCESS')
                            <span class="badge bg-success">Success</span>
                        @elseif($log->status == 'FAILED')
                            <span class="badge bg-danger">Failed</span>
                        @else
                            <span class="badge bg-info">Running</span>
                        @endif
                    </dd>

                    <dt class="col-sm-5">Config</dt>
                    <dd class="col-sm-7">
                        @if($log->promptConfig)
                            <a href="{{ route('admin.ai-prompt-configs.show', $log->promptConfig) }}">
                                {{ $log->promptConfig->name }}
                            </a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </dd>

                    <dt class="col-sm-5">Started</dt>
                    <dd class="col-sm-7">{{ $log->started_at?->format('Y-m-d H:i:s') }}</dd>

                    <dt class="col-sm-5">Finished</dt>
                    <dd class="col-sm-7">{{ $log->finished_at?->format('Y-m-d H:i:s') }}</dd>

                    <dt class="col-sm-5">Duration</dt>
                    <dd class="col-sm-7">
                        @if($log->started_at && $log->finished_at)
                            {{ $log->started_at->diffForHumans($log->finished_at, true) }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Usage</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Input Tokens</dt>
                    <dd class="col-sm-6">{{ number_format($log->tokens_input) }}</dd>

                    <dt class="col-sm-6">Output Tokens</dt>
                    <dd class="col-sm-6">{{ number_format($log->tokens_output) }}</dd>

                    <dt class="col-sm-6">Total Tokens</dt>
                    <dd class="col-sm-6"><strong>{{ number_format($log->tokens_input + $log->tokens_output) }}</strong></dd>

                    <dt class="col-sm-6">Cost Estimate</dt>
                    <dd class="col-sm-6"><strong>${{ number_format($log->cost_estimate, 4) }}</strong></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        @if($log->error_message)
        <div class="alert alert-danger">
            <strong>Error:</strong> {{ $log->error_message }}
        </div>
        @endif

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Input Payload</h5>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#inputPayload">
                    Toggle
                </button>
            </div>
            <div class="collapse show" id="inputPayload">
                <div class="card-body">
                    <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($log->input_payload_json, JSON_PRETTY_PRINT) }}</code></pre>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Output Payload</h5>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#outputPayload">
                    Toggle
                </button>
            </div>
            <div class="collapse show" id="outputPayload">
                <div class="card-body">
                    @if($log->output_payload_json)
                        <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($log->output_payload_json, JSON_PRETTY_PRINT) }}</code></pre>
                    @else
                        <p class="text-muted">No output available</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
