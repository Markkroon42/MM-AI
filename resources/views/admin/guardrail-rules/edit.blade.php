@extends('layouts.admin')

@section('title', 'Edit Guardrail Rule: ' . $rule->name)

@section('content')
<div class="mb-4">
    <h1 class="h2">Edit Guardrail Rule: {{ $rule->name }}</h1>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong>Validation Error:</strong>
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.guardrail-rules.update', $rule) }}">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-8">
                    <label for="name" class="form-label">Rule Name *</label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $rule->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">A descriptive name for this rule</small>
                </div>

                <div class="col-md-4">
                    <label for="priority" class="form-label">Priority *</label>
                    <input type="number" name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror"
                           value="{{ old('priority', $rule->priority) }}" min="1" required>
                    @error('priority')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Lower numbers execute first</small>
                </div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $rule->description) }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="applies_to_action_type" class="form-label">Applies To Action Type *</label>
                    <select name="applies_to_action_type" id="applies_to_action_type"
                            class="form-select @error('applies_to_action_type') is-invalid @enderror" required>
                        <option value="">-- Select Action Type --</option>
                        <option value="campaign_create" {{ old('applies_to_action_type', $rule->applies_to_action_type) == 'campaign_create' ? 'selected' : '' }}>Campaign Create</option>
                        <option value="campaign_update" {{ old('applies_to_action_type', $rule->applies_to_action_type) == 'campaign_update' ? 'selected' : '' }}>Campaign Update</option>
                        <option value="campaign_delete" {{ old('applies_to_action_type', $rule->applies_to_action_type) == 'campaign_delete' ? 'selected' : '' }}>Campaign Delete</option>
                        <option value="budget_update" {{ old('applies_to_action_type', $rule->applies_to_action_type) == 'budget_update' ? 'selected' : '' }}>Budget Update</option>
                        <option value="adset_create" {{ old('applies_to_action_type', $rule->applies_to_action_type) == 'adset_create' ? 'selected' : '' }}>Ad Set Create</option>
                        <option value="adset_update" {{ old('applies_to_action_type', $rule->applies_to_action_type) == 'adset_update' ? 'selected' : '' }}>Ad Set Update</option>
                        <option value="ad_create" {{ old('applies_to_action_type', $rule->applies_to_action_type) == 'ad_create' ? 'selected' : '' }}>Ad Create</option>
                        <option value="ad_update" {{ old('applies_to_action_type', $rule->applies_to_action_type) == 'ad_update' ? 'selected' : '' }}>Ad Update</option>
                        <option value="publish_job" {{ old('applies_to_action_type', $rule->applies_to_action_type) == 'publish_job' ? 'selected' : '' }}>Publish Job</option>
                    </select>
                    @error('applies_to_action_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="effect" class="form-label">Effect *</label>
                    <select name="effect" id="effect" class="form-select @error('effect') is-invalid @enderror" required>
                        <option value="">-- Select Effect --</option>
                        <option value="allow" {{ old('effect', $rule->effect) == 'allow' ? 'selected' : '' }}>Allow</option>
                        <option value="warn" {{ old('effect', $rule->effect) == 'warn' ? 'selected' : '' }}>Warn</option>
                        <option value="require_approval" {{ old('effect', $rule->effect) == 'require_approval' ? 'selected' : '' }}>Require Approval</option>
                        <option value="block" {{ old('effect', $rule->effect) == 'block' ? 'selected' : '' }}>Block</option>
                    </select>
                    @error('effect')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3">
                    <label for="severity" class="form-label">Severity *</label>
                    <select name="severity" id="severity" class="form-select @error('severity') is-invalid @enderror" required>
                        <option value="">-- Select Severity --</option>
                        <option value="low" {{ old('severity', $rule->severity) == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('severity', $rule->severity) == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('severity', $rule->severity) == 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('severity', $rule->severity) == 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                    @error('severity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="condition_expression" class="form-label">Condition Expression *</label>
                <textarea name="condition_expression" id="condition_expression" rows="6"
                          class="form-control font-monospace @error('condition_expression') is-invalid @enderror"
                          required>{{ old('condition_expression', $rule->condition_expression) }}</textarea>
                @error('condition_expression')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    Expression that evaluates to true/false. Example: <code>budget > 1000 && status == 'active'</code>
                </small>
            </div>

            <div class="mb-3">
                <label for="message_template" class="form-label">Message Template *</label>
                <textarea name="message_template" id="message_template" rows="3"
                          class="form-control @error('message_template') is-invalid @enderror"
                          required>{{ old('message_template', $rule->message_template) }}</textarea>
                @error('message_template')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">
                    Message shown when rule triggers. Use placeholders like {budget}, {campaign_name}, etc.
                </small>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           class="form-check-input" {{ old('is_active', $rule->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Active (rule will be evaluated when applied)
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.guardrail-rules.show', $rule) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> Update Guardrail Rule
                </button>
            </div>
        </form>
    </div>
</div>

<div class="mt-4">
    <div class="card border-info">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>Guardrail Rule Tips</h5>
            <ul class="mb-0">
                <li><strong>Priority:</strong> Rules with lower priority numbers are evaluated first (e.g., 10 before 100)</li>
                <li><strong>Effect Types:</strong>
                    <ul>
                        <li><strong>Allow:</strong> Explicitly allow the action (bypasses other rules)</li>
                        <li><strong>Warn:</strong> Log a warning but allow the action to proceed</li>
                        <li><strong>Require Approval:</strong> Action requires manual approval</li>
                        <li><strong>Block:</strong> Prevent the action from executing</li>
                    </ul>
                </li>
                <li><strong>Condition Expression:</strong> Should evaluate to true when the rule should trigger</li>
            </ul>
        </div>
    </div>
</div>
@endsection
