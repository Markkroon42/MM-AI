<?php

namespace App\Enums;

enum GuardrailEffectTypeEnum: string
{
    case ALLOW = 'allow';
    case BLOCK = 'block';
    case REQUIRE_APPROVAL = 'require_approval';
    case WARN = 'warn';

    public function label(): string
    {
        return match($this) {
            self::ALLOW => 'Allow',
            self::BLOCK => 'Block',
            self::REQUIRE_APPROVAL => 'Require Approval',
            self::WARN => 'Warn',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::ALLOW => 'bg-success',
            self::BLOCK => 'bg-danger',
            self::REQUIRE_APPROVAL => 'bg-warning',
            self::WARN => 'bg-info',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::ALLOW => 'Action is allowed to proceed',
            self::BLOCK => 'Action is blocked and cannot proceed',
            self::REQUIRE_APPROVAL => 'Action requires manual approval before proceeding',
            self::WARN => 'Action proceeds with a warning',
        };
    }
}
