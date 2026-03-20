<?php

namespace App\Enums;

enum MetaEntityTypeEnum: string
{
    case CAMPAIGN = 'campaign';
    case AD_SET = 'ad_set';
    case AD = 'ad';

    public function label(): string
    {
        return match($this) {
            self::CAMPAIGN => 'Campaign',
            self::AD_SET => 'Ad Set',
            self::AD => 'Ad',
        };
    }
}
