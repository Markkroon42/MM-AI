@extends('layouts.admin')

@section('title', 'Create AI Prompt Config')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Create AI Prompt Config</h1>
    <a href="{{ route('admin.ai-prompt-configs.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<div class="row">
    <div class="col-md-10">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.ai-prompt-configs.store') }}">
                    @csrf

                    <!-- Key -->
                    <div class="mb-3">
                        <label for="key" class="form-label">Key <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('key') is-invalid @enderror"
                               id="key"
                               name="key"
                               value="{{ old('key') }}"
                               required
                               autofocus>
                        <div class="form-text">Unique identifier for this configuration</div>
                        @error('key')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Agent Type -->
                    <div class="mb-3">
                        <label for="agent_type" class="form-label">Agent Type <span class="text-danger">*</span></label>
                        <select class="form-select @error('agent_type') is-invalid @enderror"
                                id="agent_type"
                                name="agent_type"
                                required>
                            <option value="">Select Agent Type</option>
                            @foreach($agentTypes as $agentType)
                                <option value="{{ $agentType->value }}" {{ old('agent_type') === $agentType->value ? 'selected' : '' }}>
                                    {{ $agentType->label() }}
                                </option>
                            @endforeach
                        </select>
                        @error('agent_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Model -->
                    <div class="mb-3">
                        <label for="model" class="form-label">Model <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control @error('model') is-invalid @enderror"
                               id="model"
                               name="model"
                               value="{{ old('model', 'gpt-4o') }}"
                               required>
                        <div class="form-text">e.g., gpt-4o, gpt-4-turbo</div>
                        @error('model')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Temperature -->
                    <div class="mb-3">
                        <label for="temperature" class="form-label">Temperature <span class="text-danger">*</span></label>
                        <input type="number"
                               class="form-control @error('temperature') is-invalid @enderror"
                               id="temperature"
                               name="temperature"
                               value="{{ old('temperature', '0.7') }}"
                               step="0.1"
                               min="0"
                               max="2"
                               required>
                        <div class="form-text">0.0 - 2.0 (lower = more focused, higher = more creative)</div>
                        @error('temperature')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Max Tokens -->
                    <div class="mb-3">
                        <label for="max_tokens" class="form-label">Max Tokens <span class="text-danger">*</span></label>
                        <input type="number"
                               class="form-control @error('max_tokens') is-invalid @enderror"
                               id="max_tokens"
                               name="max_tokens"
                               value="{{ old('max_tokens', '4000') }}"
                               min="1"
                               required>
                        @error('max_tokens')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- System Prompt -->
                    <div class="mb-3">
                        <label for="system_prompt" class="form-label">System Prompt <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('system_prompt') is-invalid @enderror"
                                  id="system_prompt"
                                  name="system_prompt"
                                  rows="8"
                                  required>{{ old('system_prompt') }}</textarea>
                        <div class="form-text">Instructions that define the AI's behavior and role</div>
                        @error('system_prompt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- User Prompt Template -->
                    <div class="mb-3">
                        <label for="user_prompt_template" class="form-label">User Prompt Template <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('user_prompt_template') is-invalid @enderror"
                                  id="user_prompt_template"
                                  name="user_prompt_template"
                                  rows="8"
                                  required>{{ old('user_prompt_template') }}</textarea>
                        <div class="form-text">Template with placeholders (e.g., {campaign_data}, {briefing})</div>
                        @error('user_prompt_template')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Response Format -->
                    <div class="mb-3">
                        <label for="response_format" class="form-label">Response Format (JSON)</label>
                        <textarea class="form-control @error('response_format') is-invalid @enderror"
                                  id="response_format"
                                  name="response_format"
                                  rows="6">{{ old('response_format') }}</textarea>
                        <div class="form-text">Optional JSON schema for structured responses</div>
                        @error('response_format')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Is Active -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="is_active"
                                   value="1"
                                   id="is_active"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Active (configuration can be used)
                            </label>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Create Config
                        </button>
                        <a href="{{ route('admin.ai-prompt-configs.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
