<?php

namespace App\Services\Orchestration;

use App\Enums\ScheduledTaskTypeEnum;

class TaskContextResolver
{
    /**
     * Resolve run context into executable parameters
     */
    public function resolve(string $taskType, ?array $context): array
    {
        return match ($taskType) {
            ScheduledTaskTypeEnum::RUN_AGENT->value => $this->resolveAgentContext($context),
            ScheduledTaskTypeEnum::GENERATE_REPORT->value => $this->resolveReportContext($context),
            ScheduledTaskTypeEnum::CREATE_KPI_SNAPSHOT->value => $this->resolveKpiSnapshotContext($context),
            ScheduledTaskTypeEnum::SYNC_META->value => $this->resolveSyncMetaContext($context),
            default => [],
        };
    }

    /**
     * Resolve agent run context
     */
    protected function resolveAgentContext(?array $context): array
    {
        return [
            'agent_type' => $context['agent_type'] ?? 'performance',
            'scope_type' => $context['scope_type'] ?? 'all',
            'scope_id' => $context['scope_id'] ?? null,
            'auto_approve' => $context['auto_approve'] ?? false,
        ];
    }

    /**
     * Resolve report generation context
     */
    protected function resolveReportContext(?array $context): array
    {
        return [
            'report_type' => $context['report_type'] ?? 'daily_summary',
            'period_days' => $context['period_days'] ?? 1,
            'include_recommendations' => $context['include_recommendations'] ?? true,
        ];
    }

    /**
     * Resolve KPI snapshot context
     */
    protected function resolveKpiSnapshotContext(?array $context): array
    {
        return [
            'snapshot_date' => $context['snapshot_date'] ?? now()->toDateString(),
            'include_trends' => $context['include_trends'] ?? true,
        ];
    }

    /**
     * Resolve Meta sync context
     */
    protected function resolveSyncMetaContext(?array $context): array
    {
        return [
            'entity_type' => $context['entity_type'] ?? 'campaigns',
            'account_id' => $context['account_id'] ?? null,
            'include_insights' => $context['include_insights'] ?? true,
        ];
    }

    /**
     * Validate context for task type
     */
    public function validate(string $taskType, ?array $context): bool
    {
        return match ($taskType) {
            ScheduledTaskTypeEnum::RUN_AGENT->value => $this->validateAgentContext($context),
            ScheduledTaskTypeEnum::GENERATE_REPORT->value => $this->validateReportContext($context),
            ScheduledTaskTypeEnum::CREATE_KPI_SNAPSHOT->value => true,
            ScheduledTaskTypeEnum::SYNC_META->value => true,
            default => true,
        };
    }

    /**
     * Validate agent context
     */
    protected function validateAgentContext(?array $context): bool
    {
        if (!$context) {
            return false;
        }

        return isset($context['agent_type']) && in_array($context['agent_type'], ['performance', 'structure']);
    }

    /**
     * Validate report context
     */
    protected function validateReportContext(?array $context): bool
    {
        if (!$context) {
            return false;
        }

        return isset($context['report_type']) && in_array($context['report_type'], ['daily_summary', 'weekly_performance']);
    }
}
