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
        Schema::create('meta_insights_daily', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_local_id');
            $table->string('entity_meta_id');
            $table->date('insight_date');
            $table->unsignedBigInteger('impressions')->nullable();
            $table->unsignedBigInteger('reach')->nullable();
            $table->unsignedBigInteger('clicks')->nullable();
            $table->unsignedBigInteger('link_clicks')->nullable();
            $table->decimal('ctr', 8, 4)->nullable();
            $table->decimal('cpc', 12, 4)->nullable();
            $table->decimal('cpm', 12, 4)->nullable();
            $table->decimal('spend', 14, 2)->nullable();
            $table->unsignedInteger('add_to_cart')->nullable();
            $table->unsignedInteger('initiate_checkout')->nullable();
            $table->unsignedInteger('purchases')->nullable();
            $table->decimal('purchase_value', 14, 2)->nullable();
            $table->decimal('roas', 12, 4)->nullable();
            $table->decimal('frequency', 8, 4)->nullable();
            $table->json('raw_payload_json')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_local_id', 'insight_date'], 'insights_entity_date_unique');
            $table->index('entity_meta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_insights_daily');
    }
};
