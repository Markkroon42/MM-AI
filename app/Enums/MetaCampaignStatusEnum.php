<?php

namespace App\Enums;

enum MetaCampaignStatusEnum: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case DELETED = 'deleted';
    case ARCHIVED = 'archived';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::DELETED => 'Deleted',
            self::ARCHIVED => 'Archived',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::ACTIVE => 'bg-success',
            self::PAUSED => 'bg-warning',
            self::DELETED => 'bg-danger',
            self::ARCHIVED => 'bg-secondary',
            self::UNKNOWN => 'bg-dark',
        };
    }
}
