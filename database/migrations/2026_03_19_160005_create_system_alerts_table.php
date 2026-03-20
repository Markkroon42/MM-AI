<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alert_type'); // 'scheduled_task_failed', 'publish_job_failed', 'ai_failure_threshold', 'stale_sync', etc.
            $table->string('severity'); // 'critical', 'high', 'medium', 'low', 'info'
            $table->string('title');
            $table->text('message');
            $table->string('status')->default('open'); // open, acknowledged, resolved, dismissed
            $table->string('related_entity_type')->nullable(); // 'scheduled_task', 'publish_job', etc.
            $table->unsignedBigInteger('related_entity_id')->nullable();
            $table->json('context_json')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index('alert_type');
            $table->index('severity');
            $table->index('status');
            $table->index(['related_entity_type', 'related_entity_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_alerts');
    }
};
