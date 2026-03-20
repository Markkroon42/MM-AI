<?php

namespace App\Enums;

enum AiUsageStatusEnum: string
{
    case RUNNING = 'RUNNING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';

    public function label(): string
    {
        return match($this) {
            self::RUNNING => 'Running',
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::RUNNING => 'bg-blue-100 text-blue-800',
            self::SUCCESS => 'bg-green-100 text-green-800',
            self::FAILED => 'bg-red-100 text-red-800',
        };
    }
}
