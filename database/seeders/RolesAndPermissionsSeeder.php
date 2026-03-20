<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view_dashboard',
            'view_meta_accounts',
            'view_campaigns',
            'view_sync_runs',
            'sync_meta_accounts',
            'sync_meta_campaigns',
            'manage_settings',
            'view_recommendations',
            'review_recommendations',
            'approve_recommendations',
            'view_campaign_briefings',
            'create_campaign_briefings',
            'view_campaign_templates',
            'manage_campaign_templates',
            'view_campaign_drafts',
            'create_campaign_drafts',
            'review_campaign_drafts',
            'approve_campaign_drafts',
            'publish_campaign_drafts',
            'view_approvals',
            'decide_approvals',
            'view_publish_jobs',
            'execute_recommendations',
            // Sprint 4: AI permissions
            'view_ai_prompt_configs',
            'manage_ai_prompt_configs',
            'view_ai_usage_logs',
            'generate_ai_copy',
            'generate_ai_creatives',
            'generate_ai_strategy',
            'review_draft_enrichments',
            'apply_draft_enrichments',
            // Sprint 5: Orchestration, Guardrails, and Reporting permissions
            'view_kpi_cockpit',
            'view_executive_reports',
            'generate_executive_reports',
            'view_scheduled_tasks',
            'manage_scheduled_tasks',
            'view_scheduled_task_runs',
            'view_guardrail_rules',
            'manage_guardrail_rules',
            'view_system_alerts',
            'resolve_system_alerts',
            'run_orchestration_tasks',
            'manage_system_settings',
            // User Management permissions
            'view_users',
            'create_users',
            'edit_users',
            'deactivate_users',
            'assign_roles',
            'reset_user_passwords',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin role - has all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

        // Marketer role - can view and sync
        $marketerRole = Role::firstOrCreate(['name' => 'marketer']);
        $marketerRole->syncPermissions([
            'view_dashboard',
            'view_meta_accounts',
            'view_campaigns',
            'view_sync_runs',
            'sync_meta_accounts',
            'sync_meta_campaigns',
            'view_recommendations',
            'review_recommendations',
            'view_campaign_briefings',
            'create_campaign_briefings',
            'view_campaign_templates',
            'view_campaign_drafts',
            'create_campaign_drafts',
            'review_campaign_drafts',
            'view_approvals',
            'view_publish_jobs',
            // Sprint 4: AI permissions for marketers
            'view_ai_usage_logs',
            'generate_ai_copy',
            'generate_ai_creatives',
            'generate_ai_strategy',
            'review_draft_enrichments',
            'apply_draft_enrichments',
            // Sprint 5: Orchestration and reporting for marketers
            'view_kpi_cockpit',
            'view_executive_reports',
            'generate_executive_reports',
            'view_scheduled_tasks',
            'view_scheduled_task_runs',
            'view_guardrail_rules',
            'view_system_alerts',
            'resolve_system_alerts',
            'manage_system_settings',
            // User Management: marketers can only view users
            'view_users',
        ]);

        // Viewer role - read-only access
        $viewerRole = Role::firstOrCreate(['name' => 'viewer']);
        $viewerRole->syncPermissions([
            'view_dashboard',
            'view_meta_accounts',
            'view_campaigns',
            'view_sync_runs',
            'view_recommendations',
            'view_campaign_briefings',
            'view_campaign_templates',
            'view_campaign_drafts',
            'view_approvals',
            'view_publish_jobs',
            // Sprint 4: AI permissions for viewers
            'view_ai_usage_logs',
            // Sprint 5: Reporting for viewers
            'view_kpi_cockpit',
            'view_executive_reports',
            // User Management: viewers cannot view users
        ]);
    }
}
