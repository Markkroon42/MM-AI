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
        Schema::create('publish_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('draft_id')->nullable()->constrained('campaign_drafts')->nullOnDelete();
            $table->string('provider');
            $table->string('action_type');
            $table->json('payload_json');
            $table->string('status');
            $table->integer('attempts')->default(0);
            $table->json('response_json')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('draft_id');
            $table->index('action_type');
            $table->index('executed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publish_jobs');
    }
};
