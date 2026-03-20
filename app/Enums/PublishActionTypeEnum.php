<?php

namespace App\Enums;

enum PublishActionTypeEnum: string
{
    case PUBLISH_CAMPAIGN_DRAFT = 'publish_campaign_draft';
    case PAUSE_CAMPAIGN = 'pause_campaign';
    case UPDATE_CAMPAIGN_BUDGET = 'update_campaign_budget';

    public function label(): string
    {
        return match($this) {
            self::PUBLISH_CAMPAIGN_DRAFT => 'Publish Campaign Draft',
            self::PAUSE_CAMPAIGN => 'Pause Campaign',
            self::UPDATE_CAMPAIGN_BUDGET => 'Update Campaign Budget',
        };
    }
}
