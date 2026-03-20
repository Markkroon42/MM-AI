<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta (Facebook) API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Meta Graph API integration
    |
    */

    'api_version' => env('META_API_VERSION', 'v22.0'),

    'graph_base_url' => env('META_GRAPH_BASE_URL', 'https://graph.facebook.com'),

    'access_token' => env('META_ACCESS_TOKEN'),

    'business_id' => env('META_BUSINESS_ID'),

    'default_account_id' => env('META_DEFAULT_ACCOUNT_ID'),

    /*
    |--------------------------------------------------------------------------
    | API Request Settings
    |--------------------------------------------------------------------------
    */

    'timeout' => env('META_API_TIMEOUT', 30),

    'retry_times' => env('META_API_RETRY_TIMES', 3),

    'retry_delay' => env('META_API_RETRY_DELAY', 1000), // milliseconds

    /*
    |--------------------------------------------------------------------------
    | Default Fields
    |--------------------------------------------------------------------------
    */

    'default_fields' => [
        'ad_accounts' => 'id,name,account_id,business,currency,timezone_name,account_status',
        'campaigns' => 'id,name,objective,buying_type,status,effective_status,daily_budget,lifetime_budget,start_time,stop_time,updated_time',
        'ad_sets' => 'id,name,optimization_goal,billing_event,bid_strategy,targeting,daily_budget,lifetime_budget,status,effective_status,start_time,end_time,updated_time',
        'ads' => 'id,name,status,effective_status,creative{id},preview_shareable_link,updated_time',
        'insights' => 'impressions,reach,clicks,inline_link_clicks,ctr,cpc,cpm,spend,actions,action_values,frequency',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'default_insights_days' => env('META_SYNC_INSIGHTS_DAYS', 30),
        'batch_size' => env('META_SYNC_BATCH_SIZE', 100),
    ],
];
