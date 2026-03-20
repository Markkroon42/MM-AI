<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executive_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type'); // 'daily_summary', 'weekly_performance', 'monthly_overview'
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status'); // 'generating', 'completed', 'failed'
            $table->json('headline_metrics_json')->nullable(); // Key KPIs: spend, revenue, roas, etc.
            $table->json('highlights_json')->nullable(); // Array of positive highlights
            $table->json('top_performers_json')->nullable(); // Top campaigns/ad sets
            $table->json('bottom_performers_json')->nullable(); // Worst campaigns/ad sets
            $table->json('issues_json')->nullable(); // Problems requiring attention
            $table->json('priorities_json')->nullable(); // Recommended actions
            $table->text('executive_summary')->nullable(); // Brief text summary
            $table->timestamp('generated_at')->nullable();
            $table->integer('generation_duration_seconds')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('report_type');
            $table->index('period_start');
            $table->index('status');
            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_reports');
    }
};
