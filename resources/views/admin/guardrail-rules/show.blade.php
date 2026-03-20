@extends('layouts.admin')

@section('title', 'Guardrail Rule: ' . $rule->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">{{ $rule->name }}</h1>
    <div>
        @can('update', $rule)
        <a href="{{ route('admin.guardrail-rules.edit', $rule) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Edit Rule
        </a>
        @endcan
        <a href="{{ route('admin.guardrail-rules.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Rule Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Priority:</strong>
                    </div>
                    <div class="col-md-9">
                        <span class="badge bg-secondary fs-6">{{ $rule->priority }}</span>
                        <small class="text-muted ms-2">(Lower numbers execute first)</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Status:</strong>
                    </div>
                    <div class="col-md-9">
                        <span class="badge bg-{{ $rule->is_active ? 'success' : 'secondary' }} fs-6">
                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                @if($rule->description)
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Description:</strong>
                    </div>
                    <div class="col-md-9">
                        {{ $rule->description }}
                    </div>
                </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Applies To:</strong>
                    </div>
                    <div class="col-md-9">
                        <code>{{ $rule->applies_to_action_type }}</code>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Effect:</strong>
                    </div>
                    <div class="col-md-9">
                        @php
                            $effectColors = [
                                'allow' => 'success',
                                'block' => 'danger',
                                'require_approval' => 'warning',
                                'warn' => 'info'
                            ];
                        @endphp
                        <span class="badge bg-{{ $effectColors[$rule->effect] ?? 'secondary' }} fs-6">
                            {{ ucfirst(str_replace('_', ' ', $rule->effect)) }}
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Severity:</strong>
                    </div>
                    <div class="col-md-9">
                        @php
                            $severityColors = [
                                'low' => 'secondary',
                                'medium' => 'info',
                                'high' => 'warning',
                                'critical' => 'danger'
                            ];
                        @endphp
                        <span class="badge bg-{{ $severityColors[$rule->severity] ?? 'secondary' }} fs-6">
                            {{ ucfirst($rule->severity) }}
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Message Template:</strong>
                    </div>
                    <div class="col-md-9">
                        <div class="alert alert-light mb-0">
                            {{ $rule->message_template }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Condition Expression</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded"><code>{{ $rule->condition_expression }}</code></pre>
                <div class="alert alert-info mb-0 mt-3">
                    <strong><i class="bi bi-info-circle me-2"></i>Note:</strong>
                    The condition expression is evaluated before the action is executed.
                    It should return true to trigger this rule.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Metadata</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <small class="text-muted">Created:</small><br>
                    {{ $rule->created_at->format('M d, Y H:i') }}
                </p>
                <p class="mb-0">
                    <small class="text-muted">Last Updated:</small><br>
                    {{ $rule->updated_at->format('M d, Y H:i') }}
                </p>
            </div>
        </div>

        @can('delete', $rule)
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Danger Zone</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">
                    Deleting this rule cannot be undone. Make sure you understand the implications.
                </p>
                <form method="POST" action="{{ route('admin.guardrail-rules.destroy', $rule) }}"
                      onsubmit="return confirm('Are you absolutely sure you want to delete this guardrail rule? This action cannot be undone.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-trash me-1"></i> Delete Rule
                    </button>
                </form>
            </div>
        </div>
        @endcan
    </div>
</div>
@endsection
