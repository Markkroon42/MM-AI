<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Guardrail Thresholds
    |--------------------------------------------------------------------------
    |
    | These thresholds are used by guardrail rules to determine if actions
    | should be blocked, require approval, or allowed with warnings.
    |
    */

    'thresholds' => [
        // Budget changes
        'max_budget_increase_percentage' => 20, // Block if > 20% increase
        'warn_budget_increase_percentage' => 10, // Warn if > 10% increase
        'max_daily_budget' => 1000, // Max daily budget without approval (€)

        // Campaign pause rules
        'min_spend_before_pause_allowed' => 100, // Must have spent at least €100
        'min_days_active_before_pause' => 3, // Must be active for at least 3 days

        // New campaign publish
        'max_initial_daily_budget' => 500, // Max initial budget without approval (€)
        'require_approval_budget_threshold' => 300, // Require approval if > €300/day

        // Recommendation execution
        'min_confidence_score' => 0.7, // Minimum confidence to auto-execute
        'block_low_confidence_critical' => 0.5, // Block critical recs with confidence < 50%
    ],

    /*
    |--------------------------------------------------------------------------
    | Action Types
    |--------------------------------------------------------------------------
    |
    | Registered action types that can have guardrails applied.
    |
    */

    'action_types' => [
        'budget_increase',
        'budget_decrease',
        'campaign_pause',
        'campaign_publish',
        'campaign_delete',
        'recommendation_execution',
        'bulk_budget_update',
    ],

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch to enable/disable all guardrails.
    | When false, all actions are allowed without checks.
    |
    */

    'enabled' => env('GUARDRAILS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Log All Decisions
    |--------------------------------------------------------------------------
    |
    | Whether to log all guardrail decisions (not just blocks/approvals).
    |
    */

    'log_all_decisions' => env('GUARDRAILS_LOG_ALL', false),
];
