@extends('layouts.admin')

@section('title', 'AI Prompt Configs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">AI Prompt Configs</h1>
    <a href="{{ route('admin.ai-prompt-configs.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Create Config
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.ai-prompt-configs.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Agent Type</label>
                <select name="agent_type" class="form-select">
                    <option value="">All</option>
                    @foreach($agentTypes as $type)
                        <option value="{{ $type->value }}" {{ request('agent_type') == $type->value ? 'selected' : '' }}>
                            {{ $type->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-select">
                    <option value="">All</option>
                    <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-secondary me-2">Filter</button>
                <a href="{{ route('admin.ai-prompt-configs.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Configs Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Name</th>
                        <th>Agent Type</th>
                        <th>Model</th>
                        <th>Temp</th>
                        <th>Max Tokens</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($configs as $config)
                    <tr>
                        <td><code>{{ $config->key }}</code></td>
                        <td>{{ $config->name }}</td>
                        <td>
                            <span class="badge bg-info">{{ $config->agent_type }}</span>
                        </td>
                        <td><small>{{ $config->model }}</small></td>
                        <td>{{ $config->temperature }}</td>
                        <td>{{ number_format($config->max_tokens) }}</td>
                        <td>
                            @if($config->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.ai-prompt-configs.show', $config) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="{{ route('admin.ai-prompt-configs.edit', $config) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No configs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $configs->links() }}
        </div>
    </div>
</div>
@endsection
