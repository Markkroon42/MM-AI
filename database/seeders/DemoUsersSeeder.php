<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@mmai.nl'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        $this->command->info('✓ Admin user created: admin@mmai.nl / password');

        // Create Marketer User
        $marketer = User::firstOrCreate(
            ['email' => 'marketer@mmai.nl'],
            [
                'name' => 'Marketer User',
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $marketerRole = Role::firstOrCreate(['name' => 'marketer']);
        if (!$marketer->hasRole('marketer')) {
            $marketer->assignRole($marketerRole);
        }

        $this->command->info('✓ Marketer user created: marketer@mmai.nl / password');

        // Create Viewer User
        $viewer = User::firstOrCreate(
            ['email' => 'viewer@mmai.nl'],
            [
                'name' => 'Viewer User',
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $viewerRole = Role::firstOrCreate(['name' => 'viewer']);
        if (!$viewer->hasRole('viewer')) {
            $viewer->assignRole($viewerRole);
        }

        $this->command->info('✓ Viewer user created: viewer@mmai.nl / password');

        // Create Inactive User (for testing)
        $inactive = User::firstOrCreate(
            ['email' => 'inactive@mmai.nl'],
            [
                'name' => 'Inactive User',
                'password' => Hash::make('password'),
                'is_active' => false,
                'must_change_password' => false,
            ]
        );

        if (!$inactive->hasRole('viewer')) {
            $inactive->assignRole($viewerRole);
        }

        $this->command->info('✓ Inactive user created: inactive@mmai.nl / password (cannot login)');

        // Create Password Change Required User
        $mustChange = User::firstOrCreate(
            ['email' => 'mustchange@mmai.nl'],
            [
                'name' => 'Must Change Password User',
                'password' => Hash::make('password'),
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        if (!$mustChange->hasRole('marketer')) {
            $mustChange->assignRole($marketerRole);
        }

        $this->command->info('✓ Must change password user created: mustchange@mmai.nl / password');

        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('Demo Users Created Successfully!');
        $this->command->info('========================================');
        $this->command->info('Admin:     admin@mmai.nl / password');
        $this->command->info('Marketer:  marketer@mmai.nl / password');
        $this->command->info('Viewer:    viewer@mmai.nl / password');
        $this->command->info('Inactive:  inactive@mmai.nl / password (cannot login)');
        $this->command->info('Must Change: mustchange@mmai.nl / password');
        $this->command->info('========================================');
    }
}
