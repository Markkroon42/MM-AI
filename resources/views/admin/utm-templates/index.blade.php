@extends('layouts.admin')

@section('title', 'UTM Templates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">UTM Templates</h1>
    <a href="{{ route('admin.utm-templates.create') }}" class="btn btn-primary">Create Template</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Source</th>
                        <th>Medium</th>
                        <th>Campaign Pattern</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>{{ $template->name }}</td>
                            <td>{{ $template->source }}</td>
                            <td>{{ $template->medium }}</td>
                            <td><code>{{ $template->campaign_pattern }}</code></td>
                            <td><span class="badge bg-{{ $template->is_active ? 'success' : 'secondary' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal{{ $template->id }}">View</button>
                            </td>
                        </tr>

                        <!-- View Modal -->
                        <div class="modal fade" id="viewModal{{ $template->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ $template->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Source:</strong> {{ $template->source }}</p>
                                        <p><strong>Medium:</strong> {{ $template->medium }}</p>
                                        <p><strong>Campaign Pattern:</strong> <code>{{ $template->campaign_pattern }}</code></p>
                                        @if($template->content_pattern)
                                            <p><strong>Content Pattern:</strong> <code>{{ $template->content_pattern }}</code></p>
                                        @endif
                                        @if($template->term_pattern)
                                            <p><strong>Term Pattern:</strong> <code>{{ $template->term_pattern }}</code></p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No templates found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $templates->links() }}</div>
    </div>
</div>
@endsection
