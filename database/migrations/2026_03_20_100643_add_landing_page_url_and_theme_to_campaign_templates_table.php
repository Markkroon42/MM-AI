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
        Schema::table('campaign_templates', function (Blueprint $table) {
            $table->string('landing_page_url')->nullable()->after('default_utm_template_id');
            $table->string('theme')->nullable()->after('funnel_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_templates', function (Blueprint $table) {
            $table->dropColumn(['landing_page_url', 'theme']);
        });
    }
};
