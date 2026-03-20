<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('draft_enrichments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_draft_id')->constrained('campaign_drafts')->cascadeOnDelete();
            $table->foreignId('ai_usage_log_id')->nullable()->constrained('ai_usage_logs')->nullOnDelete();
            $table->string('enrichment_type');
            $table->string('status')->default('DRAFT');
            $table->json('payload_json');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('campaign_draft_id');
            $table->index('status');
            $table->index('enrichment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_enrichments');
    }
};
