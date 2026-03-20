<?php

namespace App\Services\Users;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagementService
{
    /**
     * Create a new user with the provided data.
     *
     * @param array $data
     * @return User
     */
    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Hash the password if provided
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Create the user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
                'must_change_password' => $data['must_change_password'] ?? false,
            ]);

            // Assign roles if provided
            if (!empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            // Log the action
            AuditLog::log(
                'user_created',
                $user,
                null,
                [
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                    'roles' => $user->getRoleNames()->toArray(),
                ]
            );

            return $user->fresh();
        });
    }

    /**
     * Update an existing user with the provided data.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'roles' => $user->getRoleNames()->toArray(),
            ];

            // Update basic fields
            $user->update([
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'is_active' => $data['is_active'] ?? $user->is_active,
                'must_change_password' => $data['must_change_password'] ?? $user->must_change_password,
            ]);

            // Update password if provided
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                $user->save();
            }

            // Update roles if provided
            $rolesChanged = false;
            if (isset($data['roles'])) {
                $oldRoles = $user->getRoleNames()->toArray();
                $user->syncRoles($data['roles']);
                $newRoles = $user->fresh()->getRoleNames()->toArray();
                
                if ($oldRoles !== $newRoles) {
                    $rolesChanged = true;
                    
                    AuditLog::log(
                        'user_roles_updated',
                        $user,
                        ['roles' => $oldRoles],
                        ['roles' => $newRoles]
                    );
                }
            }

            $newValues = [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'roles' => $user->getRoleNames()->toArray(),
            ];

            // Log the action
            AuditLog::log(
                'user_updated',
                $user,
                $oldValues,
                $newValues
            );

            return $user->fresh();
        });
    }

    /**
     * Update a user's password.
     *
     * @param User $user
     * @param string $password
     * @param bool $mustChange
     * @return User
     */
    public function updatePassword(User $user, string $password, bool $mustChange = false): User
    {
        $user->password = Hash::make($password);
        $user->must_change_password = $mustChange;
        $user->save();

        AuditLog::log(
            'user_password_updated',
            $user,
            null,
            ['must_change_password' => $mustChange]
        );

        return $user->fresh();
    }

    /**
     * Toggle a user's active status.
     *
     * @param User $user
     * @return User
     */
    public function toggleStatus(User $user): User
    {
        $oldStatus = $user->is_active;
        $user->is_active = !$user->is_active;
        $user->save();

        $action = $user->is_active ? 'user_activated' : 'user_deactivated';

        AuditLog::log(
            $action,
            $user,
            ['is_active' => $oldStatus],
            ['is_active' => $user->is_active]
        );

        return $user->fresh();
    }

    /**
     * Check if a user can be deactivated.
     * Users cannot deactivate themselves or the last admin user.
     *
     * @param User $user
     * @param User $authUser
     * @return bool
     */
    public function canDeactivateUser(User $user, User $authUser): bool
    {
        // Cannot deactivate yourself
        if ($user->id === $authUser->id) {
            return false;
        }

        // Cannot deactivate the last admin
        if ($user->hasRole('admin')) {
            $activeAdminCount = User::active()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'admin');
                })
                ->count();

            if ($activeAdminCount <= 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the admin role can be removed from a user.
     * Cannot remove admin role from the last admin.
     *
     * @param User $user
     * @return bool
     */
    public function canRemoveAdminRole(User $user): bool
    {
        if (!$user->hasRole('admin')) {
            return true;
        }

        $activeAdminCount = User::active()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->count();

        return $activeAdminCount > 1;
    }
}
