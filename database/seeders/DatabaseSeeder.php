<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions
        $this->call([
            RolesAndPermissionsSeeder::class,
            SystemSettingsSeeder::class,
            AiPromptConfigSeeder::class,
            GuardrailRulesSeeder::class,
            ScheduledTasksSeeder::class,
            KisTemplatesSeeder::class,
            DemoUsersSeeder::class,
        ]);
    }
}
