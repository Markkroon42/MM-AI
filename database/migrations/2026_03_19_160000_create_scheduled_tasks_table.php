<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('task_type'); // 'run_agent', 'generate_report', 'sync_meta', 'create_kpi_snapshot', etc.
            $table->text('description')->nullable();
            $table->string('cron_expression'); // e.g., '0 9 * * *' for 9am daily
            $table->json('run_context_json')->nullable(); // { "agent_type": "performance", "scope": "all" }
            $table->string('status')->default('active'); // active, paused, disabled
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->integer('run_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->boolean('alert_on_failure')->default(true);
            $table->timestamps();

            $table->index('status');
            $table->index('next_run_at');
            $table->index('task_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_tasks');
    }
};
