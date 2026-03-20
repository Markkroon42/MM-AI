<?php

namespace App\Services\Guardrails;

use App\Models\AuditLog;
use App\Models\GuardrailRule;
use Illuminate\Support\Facades\Log;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class GuardrailEngine
{
    protected ExpressionLanguage $expressionLanguage;

    public function __construct(
        protected GuardrailContextBuilder $contextBuilder
    ) {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * Evaluate guardrails for a given action
     */
    public function evaluate(string $actionType, array $context): GuardrailDecision
    {
        Log::info('[GUARDRAIL_ENGINE] Evaluating guardrails', [
            'action_type' => $actionType,
            'context_keys' => array_keys($context),
        ]);

        // Get applicable rules
        $rules = GuardrailRule::active()
            ->forActionType($actionType)
            ->byPriority()
            ->get();

        if ($rules->isEmpty()) {
            Log::info('[GUARDRAIL_ENGINE] No guardrails found for action type', [
                'action_type' => $actionType,
            ]);
            return GuardrailDecision::allow();
        }

        $triggeredRules = [];
        $highestSeverity = null;
        $mostRestrictiveEffect = 'allow';

        foreach ($rules as $rule) {
            try {
                $conditionMet = $this->evaluateCondition($rule->condition_expression, $context);

                if ($conditionMet) {
                    $triggeredRules[] = [
                        'id' => $rule->id,
                        'name' => $rule->name,
                        'effect' => $rule->effect,
                        'severity' => $rule->severity,
                        'message' => $this->interpolateMessage($rule->message_template, $context),
                    ];

                    // Track highest severity
                    if ($this->isSeverityHigher($rule->severity, $highestSeverity)) {
                        $highestSeverity = $rule->severity;
                    }

                    // Determine most restrictive effect
                    if ($this->isEffectMoreRestrictive($rule->effect, $mostRestrictiveEffect)) {
                        $mostRestrictiveEffect = $rule->effect;
                    }

                    Log::info('[GUARDRAIL_ENGINE] Rule triggered', [
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                        'effect' => $rule->effect,
                        'severity' => $rule->severity,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[GUARDRAIL_ENGINE] Failed to evaluate rule', [
                    'rule_id' => $rule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Create decision based on triggered rules
        $decision = $this->createDecision($mostRestrictiveEffect, $triggeredRules, $highestSeverity);

        // Log decision
        $this->logDecision($actionType, $context, $decision);

        return $decision;
    }

    /**
     * Evaluate a condition expression against context
     */
    protected function evaluateCondition(string $expression, array $context): bool
    {
        try {
            return (bool) $this->expressionLanguage->evaluate($expression, $context);
        } catch (\Exception $e) {
            Log::error('[GUARDRAIL_ENGINE] Failed to evaluate condition', [
                'expression' => $expression,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Interpolate message template with context values
     */
    protected function interpolateMessage(string $template, array $context): string
    {
        $message = $template;

        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $message = str_replace("{{$key}}", (string) $value, $message);
            }
        }

        return $message;
    }

    /**
     * Check if severity A is higher than severity B
     */
    protected function isSeverityHigher(?string $severityA, ?string $severityB): bool
    {
        if ($severityB === null) {
            return true;
        }

        $severityOrder = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];

        return ($severityOrder[$severityA] ?? 0) > ($severityOrder[$severityB] ?? 0);
    }

    /**
     * Check if effect A is more restrictive than effect B
     */
    protected function isEffectMoreRestrictive(string $effectA, string $effectB): bool
    {
        $effectOrder = ['allow' => 1, 'warn' => 2, 'require_approval' => 3, 'block' => 4];

        return ($effectOrder[$effectA] ?? 0) > ($effectOrder[$effectB] ?? 0);
    }

    /**
     * Create decision from triggered rules
     */
    protected function createDecision(string $effect, array $triggeredRules, ?string $severity): GuardrailDecision
    {
        if (empty($triggeredRules)) {
            return GuardrailDecision::allow();
        }

        $messages = array_column($triggeredRules, 'message');
        $message = implode(' ', $messages);

        return match ($effect) {
            'block' => GuardrailDecision::block($message, $triggeredRules, $severity),
            'require_approval' => GuardrailDecision::requireApproval($message, $triggeredRules, $severity),
            'warn' => GuardrailDecision::warn($message, $triggeredRules, $severity),
            default => GuardrailDecision::allow(),
        };
    }

    /**
     * Log guardrail decision
     */
    protected function logDecision(string $actionType, array $context, GuardrailDecision $decision): void
    {
        Log::info('[GUARDRAIL_ENGINE] Decision made', [
            'action_type' => $actionType,
            'effect' => $decision->effect,
            'allowed' => $decision->allowed,
            'triggered_rules_count' => count($decision->triggeredRules ?? []),
        ]);

        // Create audit log for blocked or approval-required actions
        if ($decision->isBlocked() || $decision->requiresApproval()) {
            AuditLog::log(
                'guardrail_triggered',
                null,
                null,
                null,
                [
                    'action_type' => $actionType,
                    'effect' => $decision->effect,
                    'severity' => $decision->severity,
                    'message' => $decision->message,
                    'triggered_rules' => $decision->triggeredRules,
                    'context' => $context,
                ]
            );
        }
    }
}
