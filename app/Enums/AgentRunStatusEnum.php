<?php

namespace App\Enums;

enum AgentRunStatusEnum: string
{
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED = 'failed';

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
            self::RUNNING => 'bg-info',
            self::SUCCESS => 'bg-success',
            self::FAILED => 'bg-danger',
        };
    }
}
