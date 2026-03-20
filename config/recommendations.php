<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analysis Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for the AI recommendation analysis system.
    |
    */

    'analysis_window_days' => env('RECOMMENDATION_ANALYSIS_WINDOW_DAYS', 7),

    'min_spend_for_serious_evaluation' => env('RECOMMENDATION_MIN_SPEND', 50),

    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds used by the Performance Agent to identify opportunities
    | and issues in campaign performance.
    |
    */

    'low_ctr_threshold' => env('RECOMMENDATION_LOW_CTR_THRESHOLD', 0.5),

    'high_cpc_threshold' => env('RECOMMENDATION_HIGH_CPC_THRESHOLD', 2.0),

    'low_roas_threshold' => env('RECOMMENDATION_LOW_ROAS_THRESHOLD', 1.0),

    'high_frequency_threshold' => env('RECOMMENDATION_HIGH_FREQUENCY_THRESHOLD', 3.0),

    'budget_underutilized_ratio' => env('RECOMMENDATION_BUDGET_UNDERUTILIZED_RATIO', 0.3),

    /*
    |--------------------------------------------------------------------------
    | Scaling & Optimization Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for identifying winners to scale and losers to pause.
    |
    */

    'scale_winner_min_roas' => env('RECOMMENDATION_SCALE_WINNER_MIN_ROAS', 3.0),

    'scale_winner_min_purchases' => env('RECOMMENDATION_SCALE_WINNER_MIN_PURCHASES', 10),

    'pause_loser_min_spend' => env('RECOMMENDATION_PAUSE_LOSER_MIN_SPEND', 100),

    'pause_loser_max_roas' => env('RECOMMENDATION_PAUSE_LOSER_MAX_ROAS', 0.5),

    'creative_fatigue_frequency_threshold' => env('RECOMMENDATION_CREATIVE_FATIGUE_THRESHOLD', 4.0),

    /*
    |--------------------------------------------------------------------------
    | Naming Convention Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for campaign naming convention validation.
    |
    */

    'naming_pattern_required_segments' => env('RECOMMENDATION_NAMING_REQUIRED_SEGMENTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Confidence Scores
    |--------------------------------------------------------------------------
    |
    | Default confidence scores for different recommendation types.
    |
    */

    'confidence_scores' => [
        'structure' => 85.00,
        'performance' => 75.00,
        'optimization' => 80.00,
    ],
];
