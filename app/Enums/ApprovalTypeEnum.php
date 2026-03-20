<?php

namespace App\Enums;

enum ApprovalTypeEnum: string
{
    case RECOMMENDATION_EXECUTION = 'recommendation_execution';
    case CAMPAIGN_DRAFT_PUBLISH = 'campaign_draft_publish';

    public function label(): string
    {
        return match($this) {
            self::RECOMMENDATION_EXECUTION => 'Recommendation Execution',
            self::CAMPAIGN_DRAFT_PUBLISH => 'Campaign Draft Publish',
        };
    }
}
