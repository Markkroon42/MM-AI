<?php

namespace App\Services\Guardrails;

class GuardrailDecision
{
    public function __construct(
        public readonly string $effect,           // 'allow', 'block', 'require_approval', 'warn'
        public readonly bool $allowed,            // Can the action proceed?
        public readonly ?string $message = null,  // Message to display
        public readonly ?array $triggeredRules = null, // Rules that triggered
        public readonly ?string $severity = null  // Highest severity of triggered rules
    ) {}

    public static function allow(): self
    {
        return new self(
            effect: 'allow',
            allowed: true
        );
    }

    public static function block(string $message, array $triggeredRules = [], ?string $severity = null): self
    {
        return new self(
            effect: 'block',
            allowed: false,
            message: $message,
            triggeredRules: $triggeredRules,
            severity: $severity
        );
    }

    public static function requireApproval(string $message, array $triggeredRules = [], ?string $severity = null): self
    {
        return new self(
            effect: 'require_approval',
            allowed: false, // Not allowed to proceed directly
            message: $message,
            triggeredRules: $triggeredRules,
            severity: $severity
        );
    }

    public static function warn(string $message, array $triggeredRules = [], ?string $severity = null): self
    {
        return new self(
            effect: 'warn',
            allowed: true, // Allowed but with warning
            message: $message,
            triggeredRules: $triggeredRules,
            severity: $severity
        );
    }

    public function isBlocked(): bool
    {
        return $this->effect === 'block';
    }

    public function requiresApproval(): bool
    {
        return $this->effect === 'require_approval';
    }

    public function hasWarning(): bool
    {
        return $this->effect === 'warn';
    }

    public function canProceed(): bool
    {
        return $this->allowed;
    }
}
