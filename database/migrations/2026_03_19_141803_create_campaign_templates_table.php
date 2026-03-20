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
        Schema::create('campaign_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand');
            $table->string('market');
            $table->string('objective');
            $table->string('funnel_stage');
            $table->decimal('default_budget', 14, 2);
            $table->foreignId('default_utm_template_id')->nullable()->constrained('utm_templates')->nullOnDelete();
            $table->json('structure_json');
            $table->json('creative_rules_json');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('brand');
            $table->index('objective');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_templates');
    }
};
