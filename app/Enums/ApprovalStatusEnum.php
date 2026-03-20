<?php

namespace App\Enums;

enum ApprovalStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING => 'bg-warning',
            self::APPROVED => 'bg-success',
            self::REJECTED => 'bg-danger',
        };
    }
}
