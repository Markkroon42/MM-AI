<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaInsightDaily extends Model
{
    use HasFactory;

    protected $table = 'meta_insights_daily';

    protected $fillable = [
        'entity_type',
        'entity_local_id',
        'entity_meta_id',
        'insight_date',
        'impressions',
        'reach',
        'clicks',
        'link_clicks',
        'ctr',
        'cpc',
        'cpm',
        'spend',
        'add_to_cart',
        'initiate_checkout',
        'purchases',
        'purchase_value',
        'roas',
        'frequency',
        'raw_payload_json',
    ];

    protected $casts = [
        'insight_date' => 'date',
        'impressions' => 'integer',
        'reach' => 'integer',
        'clicks' => 'integer',
        'link_clicks' => 'integer',
        'ctr' => 'decimal:4',
        'cpc' => 'decimal:4',
        'cpm' => 'decimal:4',
        'spend' => 'decimal:2',
        'add_to_cart' => 'integer',
        'initiate_checkout' => 'integer',
        'purchases' => 'integer',
        'purchase_value' => 'decimal:2',
        'roas' => 'decimal:4',
        'frequency' => 'decimal:4',
        'raw_payload_json' => 'array',
    ];
}
