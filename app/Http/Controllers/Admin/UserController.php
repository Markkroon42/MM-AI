<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserPasswordRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Users\UserManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected UserManagementService $userManagementService
    ) {
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $this->authorize('view_users');

        $query = User::with('roles');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->inactive();
            }
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->authorize('create_users');

        $roles = Role::all();

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $user = $this->userManagementService->createUser($request->validated());

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $this->authorize('view_users');

        $user->load('roles.permissions');

        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $this->authorize('edit_users');

        $roles = Role::all();
        $user->load('roles');

        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            // Check if removing admin role
            if ($user->hasRole('admin')) {
                $newRoles = $request->input('roles', []);
                if (!in_array('admin', $newRoles)) {
                    if (!$this->userManagementService->canRemoveAdminRole($user)) {
                        return redirect()
                            ->back()
                            ->with('error', 'Cannot remove admin role from the last active admin user.');
                    }
                }
            }

            $user = $this->userManagementService->updateUser($user, $request->validated());

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Toggle the user's active status.
     */
    public function toggleStatus(Request $request, User $user)
    {
        $this->authorize('deactivate_users');

        if ($user->is_active) {
            // Trying to deactivate
            if (!$this->userManagementService->canDeactivateUser($user, auth()->user())) {
                return redirect()
                    ->back()
                    ->with('error', 'Cannot deactivate this user.');
            }
        }

        try {
            $user = $this->userManagementService->toggleStatus($user);

            $message = $user->is_active
                ? 'User activated successfully.'
                : 'User deactivated successfully.';

            return redirect()
                ->back()
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to toggle user status: ' . $e->getMessage());
        }
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(UpdateUserPasswordRequest $request, User $user)
    {
        try {
            $mustChange = $request->boolean('must_change_password', false);

            $this->userManagementService->updatePassword(
                $user,
                $request->password,
                $mustChange
            );

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', 'Password updated successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to update password: ' . $e->getMessage());
        }
    }
}
