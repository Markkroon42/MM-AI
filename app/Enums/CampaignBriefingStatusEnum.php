<?php

namespace App\Enums;

enum CampaignBriefingStatusEnum: string
{
    case DRAFT = 'draft';
    case READY_FOR_GENERATION = 'ready_for_generation';
    case GENERATED = 'generated';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::READY_FOR_GENERATION => 'Ready for Generation',
            self::GENERATED => 'Generated',
            self::ARCHIVED => 'Archived',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::DRAFT => 'bg-secondary',
            self::READY_FOR_GENERATION => 'bg-info',
            self::GENERATED => 'bg-success',
            self::ARCHIVED => 'bg-dark',
        };
    }
}
