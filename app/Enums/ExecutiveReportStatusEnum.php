<?php

namespace App\Enums;

enum ExecutiveReportStatusEnum: string
{
    case GENERATING = 'generating';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::GENERATING => 'Generating',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::GENERATING => 'bg-warning',
            self::COMPLETED => 'bg-success',
            self::FAILED => 'bg-danger',
        };
    }
}
