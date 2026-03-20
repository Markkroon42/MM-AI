<?php

namespace App\Enums;

enum RecommendationTypeEnum: string
{
    case LOW_CTR = 'low_ctr';
    case HIGH_CPC = 'high_cpc';
    case LOW_ROAS = 'low_roas';
    case HIGH_FREQUENCY = 'high_frequency';
    case NO_AD_SETS = 'no_ad_sets';
    case NO_ADS = 'no_ads';
    case MISSING_UTM = 'missing_utm';
    case NAMING_VIOLATION = 'naming_violation';
    case DUPLICATE_STRUCTURE = 'duplicate_structure';
    case INACTIVE_BUT_SPENDING = 'inactive_but_spending';
    case BUDGET_UNDERUTILIZED = 'budget_underutilized';
    case SPEND_WITHOUT_PURCHASES = 'spend_without_purchases';
    case CREATIVE_FATIGUE = 'creative_fatigue';
    case SCALE_WINNER = 'scale_winner';
    case PAUSE_LOSER = 'pause_loser';

    public function label(): string
    {
        return match($this) {
            self::LOW_CTR => 'Low CTR',
            self::HIGH_CPC => 'High CPC',
            self::LOW_ROAS => 'Low ROAS',
            self::HIGH_FREQUENCY => 'High Frequency',
            self::NO_AD_SETS => 'No Ad Sets',
            self::NO_ADS => 'No Ads',
            self::MISSING_UTM => 'Missing UTM',
            self::NAMING_VIOLATION => 'Naming Violation',
            self::DUPLICATE_STRUCTURE => 'Duplicate Structure',
            self::INACTIVE_BUT_SPENDING => 'Inactive But Spending',
            self::BUDGET_UNDERUTILIZED => 'Budget Underutilized',
            self::SPEND_WITHOUT_PURCHASES => 'Spend Without Purchases',
            self::CREATIVE_FATIGUE => 'Creative Fatigue',
            self::SCALE_WINNER => 'Scale Winner',
            self::PAUSE_LOSER => 'Pause Loser',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::LOW_CTR => 'Campaign has click-through rate below threshold',
            self::HIGH_CPC => 'Campaign has cost per click above threshold',
            self::LOW_ROAS => 'Campaign has return on ad spend below threshold',
            self::HIGH_FREQUENCY => 'Campaign has frequency above threshold indicating ad fatigue',
            self::NO_AD_SETS => 'Campaign has no ad sets configured',
            self::NO_ADS => 'Ad set has no ads configured',
            self::MISSING_UTM => 'Campaign name is missing UTM tracking parameters',
            self::NAMING_VIOLATION => 'Campaign name does not follow naming convention',
            self::DUPLICATE_STRUCTURE => 'Similar campaign structure detected in account',
            self::INACTIVE_BUT_SPENDING => 'Campaign is paused or deleted but still recording spend',
            self::BUDGET_UNDERUTILIZED => 'Campaign is not spending allocated budget',
            self::SPEND_WITHOUT_PURCHASES => 'Campaign is spending money without generating purchases',
            self::CREATIVE_FATIGUE => 'Creative is showing signs of fatigue with declining performance',
            self::SCALE_WINNER => 'Campaign is performing well and could be scaled',
            self::PAUSE_LOSER => 'Campaign is consistently underperforming and should be paused',
        };
    }
}
