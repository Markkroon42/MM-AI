<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardrail_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('applies_to_action_type'); // 'budget_increase', 'campaign_pause', 'campaign_publish', etc.
            $table->string('condition_expression'); // e.g., 'budget_increase_percentage > 20'
            $table->string('effect'); // 'block', 'require_approval', 'warn', 'allow'
            $table->string('severity'); // 'critical', 'high', 'medium', 'low'
            $table->text('message_template'); // Message shown when rule triggers
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(100); // Lower number = higher priority
            $table->timestamps();

            $table->index('applies_to_action_type');
            $table->index('is_active');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardrail_rules');
    }
};
