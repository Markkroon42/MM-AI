<?php

namespace App\Enums;

enum DraftEnrichmentStatusEnum: string
{
    case DRAFT = 'DRAFT';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case APPLIED = 'APPLIED';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::APPLIED => 'Applied',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::DRAFT => 'bg-gray-100 text-gray-800',
            self::APPROVED => 'bg-green-100 text-green-800',
            self::REJECTED => 'bg-red-100 text-red-800',
            self::APPLIED => 'bg-blue-100 text-blue-800',
        };
    }
}
