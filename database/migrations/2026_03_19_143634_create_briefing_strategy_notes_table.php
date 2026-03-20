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
        Schema::create('briefing_strategy_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_briefing_id')->constrained('campaign_briefings')->cascadeOnDelete();
            $table->foreignId('ai_usage_log_id')->nullable()->constrained('ai_usage_logs')->nullOnDelete();
            $table->json('strategy_payload_json');
            $table->timestamps();

            $table->index('campaign_briefing_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('briefing_strategy_notes');
    }
};
