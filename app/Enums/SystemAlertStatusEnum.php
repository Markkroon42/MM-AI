<?php

namespace App\Enums;

enum SystemAlertStatusEnum: string
{
    case OPEN = 'open';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';
    case DISMISSED = 'dismissed';

    public function label(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::ACKNOWLEDGED => 'Acknowledged',
            self::RESOLVED => 'Resolved',
            self::DISMISSED => 'Dismissed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::OPEN => 'bg-danger',
            self::ACKNOWLEDGED => 'bg-warning',
            self::RESOLVED => 'bg-success',
            self::DISMISSED => 'bg-secondary',
        };
    }
}
