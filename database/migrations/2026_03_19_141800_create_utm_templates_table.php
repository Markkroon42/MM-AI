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
        Schema::create('utm_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source');
            $table->string('medium');
            $table->string('campaign_pattern');
            $table->string('content_pattern')->nullable();
            $table->string('term_pattern')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utm_templates');
    }
};
