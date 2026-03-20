<?php

namespace Database\Seeders;

use App\Models\GuardrailRule;
use Illuminate\Database\Seeder;

class GuardrailRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Block Large Budget Increases',
                'description' => 'Prevent budget increases greater than 20% without approval',
                'applies_to_action_type' => 'budget_increase',
                'condition_expression' => 'budget_increase_percentage > 20',
                'effect' => 'block',
                'severity' => 'high',
                'message_template' => 'Budget increase of {budget_increase_percentage}% exceeds 20% limit. Please request approval for large budget changes.',
                'is_active' => true,
                'priority' => 10,
            ],
            [
                'name' => 'Require Approval for Medium Budget Increases',
                'description' => 'Require approval for budget increases between 10-20%',
                'applies_to_action_type' => 'budget_increase',
                'condition_expression' => 'budget_increase_percentage > 10 and budget_increase_percentage <= 20',
                'effect' => 'require_approval',
                'severity' => 'medium',
                'message_template' => 'Budget increase of {budget_increase_percentage}% requires approval.',
                'is_active' => true,
                'priority' => 20,
            ],
            [
                'name' => 'Block Campaign Pause with Low Spend',
                'description' => 'Prevent pausing campaigns that have spent less than €100',
                'applies_to_action_type' => 'campaign_pause',
                'condition_expression' => 'current_spend < 100',
                'effect' => 'block',
                'severity' => 'medium',
                'message_template' => 'Cannot pause campaign with only €{current_spend} spent. Minimum spend of €100 required for meaningful data.',
                'is_active' => true,
                'priority' => 10,
            ],
            [
                'name' => 'Warn on Campaign Pause Without Conversions',
                'description' => 'Warn when pausing campaigns that have conversions',
                'applies_to_action_type' => 'campaign_pause',
                'condition_expression' => 'has_conversions == true',
                'effect' => 'warn',
                'severity' => 'low',
                'message_template' => 'Warning: This campaign has generated conversions. Consider reviewing performance before pausing.',
                'is_active' => true,
                'priority' => 30,
            ],
            [
                'name' => 'Require Approval for High Initial Budgets',
                'description' => 'New campaigns with daily budget > €300 require approval',
                'applies_to_action_type' => 'campaign_publish',
                'condition_expression' => 'daily_budget > 300',
                'effect' => 'require_approval',
                'severity' => 'high',
                'message_template' => 'Daily budget of €{daily_budget} requires approval. Consider starting with a lower test budget.',
                'is_active' => true,
                'priority' => 10,
            ],
            [
                'name' => 'Block Low Confidence Critical Recommendations',
                'description' => 'Prevent execution of critical recommendations with low confidence',
                'applies_to_action_type' => 'recommendation_execution',
                'condition_expression' => 'severity == "critical" and confidence_score < 0.5',
                'effect' => 'block',
                'severity' => 'critical',
                'message_template' => 'Critical recommendation with only {confidence_score} confidence cannot be auto-executed. Manual review required.',
                'is_active' => true,
                'priority' => 5,
            ],
            [
                'name' => 'Warn on Budget Increase Without Sufficient Data',
                'description' => 'Warn when increasing budget for campaigns with little spend history',
                'applies_to_action_type' => 'budget_increase',
                'condition_expression' => 'has_sufficient_data == false',
                'effect' => 'warn',
                'severity' => 'medium',
                'message_template' => 'Limited performance data available. Campaign has only spent €{current_spend}. Consider waiting for more data before scaling.',
                'is_active' => true,
                'priority' => 25,
            ],
        ];

        foreach ($rules as $rule) {
            GuardrailRule::firstOrCreate(
                [
                    'name' => $rule['name'],
                    'applies_to_action_type' => $rule['applies_to_action_type'],
                ],
                $rule
            );
        }
    }
}
