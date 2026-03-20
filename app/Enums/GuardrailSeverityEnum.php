<?php

namespace App\Enums;

enum GuardrailSeverityEnum: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function label(): string
    {
        return match($this) {
            self::CRITICAL => 'Critical',
            self::HIGH => 'High',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::CRITICAL => 'bg-danger',
            self::HIGH => 'bg-warning',
            self::MEDIUM => 'bg-info',
            self::LOW => 'bg-secondary',
        };
    }
}
