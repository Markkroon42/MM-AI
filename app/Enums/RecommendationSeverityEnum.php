<?php

namespace App\Enums;

enum RecommendationSeverityEnum: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::CRITICAL => 'Critical',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::LOW => 'bg-info',
            self::MEDIUM => 'bg-warning',
            self::HIGH => 'bg-danger',
            self::CRITICAL => 'bg-dark',
        };
    }
}
