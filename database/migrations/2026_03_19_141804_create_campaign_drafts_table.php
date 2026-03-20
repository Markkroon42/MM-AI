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
        Schema::create('campaign_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('briefing_id')->nullable()->constrained('campaign_briefings')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('campaign_templates')->nullOnDelete();
            $table->string('generated_name');
            $table->json('draft_payload_json');
            $table->string('status')->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('briefing_id');
            $table->index('template_id');
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_drafts');
    }
};
