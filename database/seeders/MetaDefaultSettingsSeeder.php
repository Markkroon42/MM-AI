<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class MetaDefaultSettingsSeeder extends Seeder
{
    /**
     * Seed default Meta settings for publish flow fallback
     */
    public function run(): void
    {
        // Set default Meta ad account ID if configured in environment
        if ($defaultAccountId = config('meta.default_account_id')) {
            SystemSetting::updateOrCreate(
                ['group' => 'meta', 'key' => 'default_account_id'],
                [
                    'value' => $defaultAccountId,
                    'type' => 'string',
                ]
            );

            $this->command->info("Meta default account ID set to: {$defaultAccountId}");
        } else {
            $this->command->warn('META_DEFAULT_ACCOUNT_ID not set in environment - skipping');
        }
    }
}
