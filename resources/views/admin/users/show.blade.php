@extends('layouts.admin')

@section('title', 'User Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">User Details</h1>
    <div>
        @can('edit_users')
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Edit User
        </a>
        @endcan
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- User Information Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">User Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Name:</strong>
                    </div>
                    <div class="col-md-8">
                        {{ $user->name }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Email:</strong>
                    </div>
                    <div class="col-md-8">
                        {{ $user->email }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Status:</strong>
                    </div>
                    <div class="col-md-8">
                        @if($user->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Must Change Password:</strong>
                    </div>
                    <div class="col-md-8">
                        @if($user->must_change_password)
                            <span class="badge bg-warning">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Last Login:</strong>
                    </div>
                    <div class="col-md-8">
                        @if($user->last_login_at)
                            {{ $user->last_login_at->format('M d, Y H:i:s') }}
                            <small class="text-muted">({{ $user->last_login_at->diffForHumans() }})</small>
                        @else
                            <span class="text-muted">Never logged in</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Created:</strong>
                    </div>
                    <div class="col-md-8">
                        {{ $user->created_at->format('M d, Y H:i:s') }}
                        <small class="text-muted">({{ $user->created_at->diffForHumans() }})</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Updated:</strong>
                    </div>
                    <div class="col-md-8">
                        {{ $user->updated_at->format('M d, Y H:i:s') }}
                        <small class="text-muted">({{ $user->updated_at->diffForHumans() }})</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles & Permissions Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Roles & Permissions</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Assigned Roles:</strong>
                    <div class="mt-2">
                        @forelse($user->roles as $role)
                            <span class="badge bg-{{ $role->name === 'admin' ? 'danger' : ($role->name === 'marketer' ? 'primary' : 'secondary') }} me-1">
                                {{ ucfirst($role->name) }}
                            </span>
                        @empty
                            <span class="text-muted">No roles assigned</span>
                        @endforelse
                    </div>
                </div>

                @if($user->roles->count() > 0)
                <div class="mt-4">
                    <strong>Permissions (via roles):</strong>
                    <div class="mt-2">
                        @php
                            $allPermissions = $user->roles->flatMap(function($role) {
                                return $role->permissions;
                            })->unique('id')->sortBy('name');
                        @endphp
                        
                        @if($allPermissions->count() > 0)
                            <div class="row">
                                @foreach($allPermissions as $permission)
                                    <div class="col-md-6 mb-1">
                                        <small class="text-muted">
                                            <i class="bi bi-check-circle text-success"></i> {{ str_replace('_', ' ', ucfirst($permission->name)) }}
                                        </small>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted">No permissions</span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Actions Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                @can('deactivate_users')
                <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="mb-3">
                    @csrf
                    @method('PATCH')
                    <button type="submit" 
                            class="btn btn-{{ $user->is_active ? 'warning' : 'success' }} w-100"
                            onclick="return confirm('Are you sure you want to {{ $user->is_active ? 'deactivate' : 'activate' }} this user?')">
                        <i class="bi bi-{{ $user->is_active ? 'x-circle' : 'check-circle' }}"></i>
                        {{ $user->is_active ? 'Deactivate User' : 'Activate User' }}
                    </button>
                </form>
                @endcan

                @can('reset_user_passwords')
                <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#passwordModal">
                    <i class="bi bi-key"></i> Reset Password
                </button>
                @endcan
            </div>
        </div>
    </div>
</div>

<!-- Password Reset Modal -->
@can('reset_user_passwords')
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.users.update-password', $user) }}">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">Reset Password for {{ $user->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password" required>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" 
                               name="password_confirmation" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="must_change_password" 
                               name="must_change_password" value="1">
                        <label class="form-check-label" for="must_change_password">
                            Require user to change password on next login
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection
