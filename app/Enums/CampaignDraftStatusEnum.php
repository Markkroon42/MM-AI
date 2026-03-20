<?php

namespace App\Enums;

enum CampaignDraftStatusEnum: string
{
    case DRAFT = 'draft';
    case READY_FOR_REVIEW = 'ready_for_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PUBLISHING = 'publishing';
    case PUBLISHED = 'published';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::READY_FOR_REVIEW => 'Ready for Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::PUBLISHING => 'Publishing',
            self::PUBLISHED => 'Published',
            self::FAILED => 'Failed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::DRAFT => 'bg-secondary',
            self::READY_FOR_REVIEW => 'bg-info',
            self::APPROVED => 'bg-success',
            self::REJECTED => 'bg-danger',
            self::PUBLISHING => 'bg-warning',
            self::PUBLISHED => 'bg-primary',
            self::FAILED => 'bg-danger',
        };
    }
}
