@extends('layouts.admin')

@section('title', 'Campaign Templates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Campaign Templates</h1>
    <a href="{{ route('admin.campaign-templates.create') }}" class="btn btn-primary">Create Template</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Brand</th>
                        <th>Market</th>
                        <th>Objective</th>
                        <th>Funnel Stage</th>
                        <th>Default Budget</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td>{{ $template->brand }}</td>
                            <td>{{ $template->market }}</td>
                            <td>{{ $template->objective }}</td>
                            <td>{{ $template->funnel_stage }}</td>
                            <td>${{ number_format($template->default_budget, 2) }}</td>
                            <td><span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <a href="{{ route('admin.campaign-templates.show', $template) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No templates found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $templates->links() }}</div>
    </div>
</div>
@endsection
