<?php

namespace App\Enums;

enum RecommendationStatusEnum: string
{
    case NEW = 'new';
    case REVIEWING = 'reviewing';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXECUTED = 'executed';

    public function label(): string
    {
        return match($this) {
            self::NEW => 'New',
            self::REVIEWING => 'Reviewing',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::EXECUTED => 'Executed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::NEW => 'bg-primary',
            self::REVIEWING => 'bg-warning',
            self::APPROVED => 'bg-success',
            self::REJECTED => 'bg-danger',
            self::EXECUTED => 'bg-info',
        };
    }
}
