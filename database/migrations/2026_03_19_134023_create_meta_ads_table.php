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
        Schema::create('meta_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_ad_set_id')->constrained()->cascadeOnDelete();
            $table->string('meta_ad_id')->unique();
            $table->string('name');
            $table->string('status', 50)->nullable();
            $table->string('effective_status', 50)->nullable();
            $table->string('creative_meta_id')->nullable();
            $table->text('preview_url')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('raw_payload_json')->nullable();
            $table->timestamps();

            $table->index('meta_ad_set_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_ads');
    }
};
