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
        Schema::create('campaign_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_campaign_id')->nullable()->constrained('meta_campaigns')->nullOnDelete();
            $table->foreignId('meta_ad_set_id')->nullable()->constrained('meta_ad_sets')->nullOnDelete();
            $table->foreignId('meta_ad_id')->nullable()->constrained('meta_ads')->nullOnDelete();
            $table->string('recommendation_type');
            $table->string('severity');
            $table->string('title');
            $table->text('explanation');
            $table->text('proposed_action');
            $table->json('action_payload_json')->nullable();
            $table->string('source_agent');
            $table->decimal('confidence_score', 5, 2)->default(0.00);
            $table->string('status')->default('new');
            $table->foreignId('created_by_run_id')->nullable()->constrained('agent_runs')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('recommendation_type');
            $table->index('severity');
            $table->index('source_agent');
            $table->index('meta_campaign_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_recommendations');
    }
};
