<?php

namespace App\Enums;

enum ScheduledTaskStatusEnum: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case DISABLED = 'disabled';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::PAUSED => 'Paused',
            self::DISABLED => 'Disabled',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::ACTIVE => 'bg-success',
            self::PAUSED => 'bg-warning',
            self::DISABLED => 'bg-secondary',
        };
    }
}
