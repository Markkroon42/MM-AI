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
        Schema::create('meta_ad_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('meta_ad_set_id')->unique();
            $table->string('name');
            $table->string('optimization_goal')->nullable();
            $table->string('billing_event')->nullable();
            $table->string('bid_strategy')->nullable();
            $table->json('targeting_json')->nullable();
            $table->decimal('daily_budget', 14, 2)->nullable();
            $table->decimal('lifetime_budget', 14, 2)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('effective_status', 50)->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload_json')->nullable();
            $table->timestamps();

            $table->index('meta_campaign_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_ad_sets');
    }
};
