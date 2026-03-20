<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'view_users']);
        Permission::create(['name' => 'create_users']);
        Permission::create(['name' => 'edit_users']);
        Permission::create(['name' => 'deactivate_users']);
        Permission::create(['name' => 'assign_roles']);
        Permission::create(['name' => 'reset_user_passwords']);

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $marketerRole = Role::create(['name' => 'marketer']);
        $marketerRole->givePermissionTo(['view_users']);

        Role::create(['name' => 'viewer']);
    }

    public function test_users_index_requires_permission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_users()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $otherUser = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee($otherUser->name);
        $response->assertSee($otherUser->email);
    }

    public function test_admin_can_create_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['marketer'],
            'is_active' => true,
            'must_change_password' => false,
        ];

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('marketer'));
    }

    public function test_admin_can_update_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);
        $user->assignRole('viewer');

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'roles' => ['marketer'],
            'is_active' => true,
            'must_change_password' => false,
        ];

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('marketer'));
        $this->assertFalse($user->hasRole('viewer'));
    }

    public function test_admin_can_toggle_user_status()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->patch(route('admin.users.toggle-status', $user));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);

        // Toggle back
        $response = $this->actingAs($admin)->patch(route('admin.users.toggle-status', $user));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_user_password()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        $response = $this->actingAs($admin)->patch(route('admin.users.update-password', $user), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'must_change_password' => true,
        ]);

        $response->assertRedirect();
        
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertTrue($user->must_change_password);
    }

    public function test_user_cannot_deactivate_themselves()
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->patch(route('admin.users.toggle-status', $admin));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        
        $admin->refresh();
        $this->assertTrue($admin->is_active);
    }

    public function test_cannot_deactivate_last_admin()
    {
        $admin1 = User::factory()->create(['is_active' => true]);
        $admin1->assignRole('admin');

        $admin2 = User::factory()->create(['is_active' => true]);
        $admin2->assignRole('admin');

        // Deactivate admin2 first (should work, admin1 is still active)
        $response = $this->actingAs($admin1)->patch(route('admin.users.toggle-status', $admin2));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $admin2->refresh();
        $this->assertFalse($admin2->is_active);

        // Try to deactivate admin1 (should fail, it's the last active admin)
        $anotherAdmin = User::factory()->create();
        $anotherAdmin->assignRole('admin');

        $response = $this->actingAs($anotherAdmin)->patch(route('admin.users.toggle-status', $admin1));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $admin1->refresh();
        $this->assertTrue($admin1->is_active);
    }

    public function test_cannot_remove_admin_role_from_last_admin()
    {
        $admin1 = User::factory()->create();
        $admin1->assignRole('admin');

        $admin2 = User::factory()->create();
        $admin2->assignRole('admin');

        // Deactivate admin2
        $admin2->is_active = false;
        $admin2->save();

        // Try to remove admin role from admin1 (the last active admin)
        $response = $this->actingAs($admin1)->patch(route('admin.users.update', $admin1), [
            'name' => $admin1->name,
            'email' => $admin1->email,
            'roles' => ['marketer'], // Trying to change from admin to marketer
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $admin1->refresh();
        $this->assertTrue($admin1->hasRole('admin'));
    }

    public function test_inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_email_must_be_unique()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'Test User',
            'email' => 'existing@example.com', // Duplicate email
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => true,
        ];

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_is_hashed()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $plainPassword = 'password123';

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $plainPassword,
            'password_confirmation' => $plainPassword,
            'is_active' => true,
        ];

        $this->actingAs($admin)->post(route('admin.users.store'), $userData);

        $user = User::where('email', 'test@example.com')->first();
        
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    public function test_roles_sync_correctly()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $user->assignRole('viewer');

        $this->assertTrue($user->hasRole('viewer'));
        $this->assertFalse($user->hasRole('marketer'));

        // Update roles
        $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => ['marketer', 'admin'],
            'is_active' => true,
            'must_change_password' => false,
        ]);

        $user->refresh();
        
        $this->assertTrue($user->hasRole('marketer'));
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('viewer'));
    }

    public function test_user_without_permission_cannot_create_user()
    {
        $marketer = User::factory()->create();
        $marketer->assignRole('marketer'); // Only has view_users permission

        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($marketer)->post(route('admin.users.store'), $userData);

        $response->assertStatus(403);
    }

    public function test_last_login_is_tracked()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'last_login_at' => null,
        ]);

        $this->assertNull($user->last_login_at);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_user_scopes_work_correctly()
    {
        User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => false]);
        User::factory()->create(['is_active' => false]);

        $activeCount = User::active()->count();
        $inactiveCount = User::inactive()->count();

        $this->assertEquals(2, $activeCount);
        $this->assertEquals(2, $inactiveCount);
    }
}
