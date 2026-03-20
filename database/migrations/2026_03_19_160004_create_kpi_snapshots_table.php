<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->integer('active_campaigns_count')->default(0);
            $table->integer('active_ad_sets_count')->default(0);
            $table->integer('active_ads_count')->default(0);
            $table->decimal('total_spend', 15, 2)->default(0);
            $table->integer('total_impressions')->default(0);
            $table->integer('total_clicks')->default(0);
            $table->decimal('avg_cpc', 10, 4)->default(0);
            $table->decimal('avg_ctr', 10, 4)->default(0);
            $table->integer('total_conversions')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('avg_roas', 10, 4)->default(0);
            $table->integer('pending_recommendations_count')->default(0);
            $table->integer('approved_recommendations_count')->default(0);
            $table->integer('executed_recommendations_count')->default(0);
            $table->integer('pending_approvals_count')->default(0);
            $table->integer('pending_publish_jobs_count')->default(0);
            $table->integer('open_alerts_count')->default(0);
            $table->json('additional_metrics_json')->nullable();
            $table->timestamps();

            $table->unique('snapshot_date');
            $table->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_snapshots');
    }
};
