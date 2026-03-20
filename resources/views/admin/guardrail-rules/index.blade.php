@extends('layouts.admin')

@section('title', 'Guardrail Rules')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Guardrail Rules</h1>
    @can('create', App\Models\GuardrailRule::class)
    <a href="{{ route('admin.guardrail-rules.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Create Rule
    </a>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Name</th>
                        <th>Applies To</th>
                        <th>Effect</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $rule->priority }}</span>
                            </td>
                            <td>
                                <strong>{{ $rule->name }}</strong>
                                @if($rule->description)
                                    <br><small class="text-muted">{{ Str::limit($rule->description, 50) }}</small>
                                @endif
                            </td>
                            <td><code>{{ $rule->applies_to_action_type }}</code></td>
                            <td>
                                @php
                                    $effectColors = [
                                        'allow' => 'success',
                                        'block' => 'danger',
                                        'require_approval' => 'warning',
                                        'warn' => 'info'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $effectColors[$rule->effect] ?? 'secondary' }}">
                                    {{ ucfirst($rule->effect) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $severityColors = [
                                        'low' => 'secondary',
                                        'medium' => 'info',
                                        'high' => 'warning',
                                        'critical' => 'danger'
                                    ];
                                @endphp
                                <span class="badge bg-{{ $severityColors[$rule->severity] ?? 'secondary' }}">
                                    {{ ucfirst($rule->severity) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $rule->is_active ? 'success' : 'secondary' }}">
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.guardrail-rules.show', $rule) }}"
                                       class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('update', $rule)
                                    <a href="{{ route('admin.guardrail-rules.edit', $rule) }}"
                                       class="btn btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                    @can('delete', $rule)
                                    <form method="POST"
                                          action="{{ route('admin.guardrail-rules.destroy', $rule) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this rule?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No guardrail rules found.
                                @can('create', App\Models\GuardrailRule::class)
                                    <a href="{{ route('admin.guardrail-rules.create') }}">Create your first rule</a>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $rules->links() }}
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="card border-info">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>About Guardrail Rules</h5>
            <p class="card-text mb-2">
                Guardrail rules protect your campaigns by enforcing policies before actions are executed.
            </p>
            <ul class="mb-0">
                <li><strong>Priority:</strong> Lower numbers execute first</li>
                <li><strong>Effect:</strong> Allow, Block, Require Approval, or Warn</li>
                <li><strong>Applies To:</strong> Specific action types (e.g., campaign_create, budget_update)</li>
            </ul>
        </div>
    </div>
</div>
@endsection
