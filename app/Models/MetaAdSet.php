<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaAdSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_campaign_id',
        'meta_ad_set_id',
        'name',
        'optimization_goal',
        'billing_event',
        'bid_strategy',
        'targeting_json',
        'daily_budget',
        'lifetime_budget',
        'status',
        'effective_status',
        'start_time',
        'end_time',
        'source_updated_at',
        'last_synced_at',
        'raw_payload_json',
    ];

    protected $casts = [
        'targeting_json' => 'array',
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'source_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'raw_payload_json' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MetaCampaign::class, 'meta_campaign_id');
    }

    public function ads(): HasMany
    {
        return $this->hasMany(MetaAd::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(MetaInsightDaily::class, 'entity_local_id')
            ->where('entity_type', 'ad_set');
    }
}
