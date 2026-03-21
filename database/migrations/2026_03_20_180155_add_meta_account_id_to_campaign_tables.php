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
        // Add meta_account_id to campaign_briefings
        Schema::table('campaign_briefings', function (Blueprint $table) {
            $table->string('meta_account_id')->nullable()->after('status');
        });

        // Add meta_account_id to campaign_templates
        Schema::table('campaign_templates', function (Blueprint $table) {
            $table->string('meta_account_id')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_briefings', function (Blueprint $table) {
            $table->dropColumn('meta_account_id');
        });

        Schema::table('campaign_templates', function (Blueprint $table) {
            $table->dropColumn('meta_account_id');
        });
    }
};
