<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Meta API Settings
            [
                'group' => 'meta_api',
                'key' => 'api_version',
                'value' => 'v22.0',
                'type' => 'string',
            ],
            [
                'group' => 'meta_api',
                'key' => 'default_insights_days',
                'value' => '30',
                'type' => 'integer',
            ],
            [
                'group' => 'meta_api',
                'key' => 'batch_size',
                'value' => '100',
                'type' => 'integer',
            ],
            [
                'group' => 'meta_api',
                'key' => 'timeout',
                'value' => '30',
                'type' => 'integer',
            ],
            [
                'group' => 'meta_api',
                'key' => 'retry_times',
                'value' => '3',
                'type' => 'integer',
            ],

            // Sync Settings
            [
                'group' => 'sync',
                'key' => 'auto_sync_enabled',
                'value' => 'false',
                'type' => 'boolean',
            ],
            [
                'group' => 'sync',
                'key' => 'auto_sync_interval',
                'value' => '60',
                'type' => 'integer',
            ],

            // Notification Settings
            [
                'group' => 'notifications',
                'key' => 'sync_failure_notifications',
                'value' => 'true',
                'type' => 'boolean',
            ],
            [
                'group' => 'notifications',
                'key' => 'notification_email',
                'value' => 'admin@example.com',
                'type' => 'string',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }
    }
}
