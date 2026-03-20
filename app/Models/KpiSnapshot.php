<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_date',
        'active_campaigns_count',
        'active_ad_sets_count',
        'active_ads_count',
        'total_spend',
        'total_impressions',
        'total_clicks',
        'avg_cpc',
        'avg_ctr',
        'total_conversions',
        'total_revenue',
        'avg_roas',
        'pending_recommendations_count',
        'approved_recommendations_count',
        'executed_recommendations_count',
        'pending_approvals_count',
        'pending_publish_jobs_count',
        'open_alerts_count',
        'additional_metrics_json',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'total_spend' => 'decimal:2',
        'avg_cpc' => 'decimal:4',
        'avg_ctr' => 'decimal:4',
        'total_revenue' => 'decimal:2',
        'avg_roas' => 'decimal:4',
        'additional_metrics_json' => 'array',
    ];

    /**
     * Get snapshot for a specific date
     */
    public static function forDate(\Carbon\Carbon $date): ?self
    {
        return self::where('snapshot_date', $date->toDateString())->first();
    }

    /**
     * Get latest snapshot
     */
    public static function latest(): ?self
    {
        return self::orderBy('snapshot_date', 'desc')->first();
    }

    /**
     * Get snapshots for date range
     */
    public static function forDateRange(\Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        return self::whereBetween('snapshot_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('snapshot_date', 'asc')
            ->get();
    }
}
