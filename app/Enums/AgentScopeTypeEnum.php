<?php

namespace App\Enums;

enum AgentScopeTypeEnum: string
{
    case SYSTEM = 'system';
    case ACCOUNT = 'account';
    case CAMPAIGN = 'campaign';
    case AD_SET = 'ad_set';
    case AD = 'ad';

    public function label(): string
    {
        return match($this) {
            self::SYSTEM => 'System',
            self::ACCOUNT => 'Account',
            self::CAMPAIGN => 'Campaign',
            self::AD_SET => 'Ad Set',
            self::AD => 'Ad',
        };
    }
}
