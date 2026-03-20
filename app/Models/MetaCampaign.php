<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_ad_account_id',
        'meta_campaign_id',
        'name',
        'objective',
        'buying_type',
        'status',
        'effective_status',
        'daily_budget',
        'lifetime_budget',
        'start_time',
        'stop_time',
        'source_updated_at',
        'last_synced_at',
        'raw_payload_json',
    ];

    protected $casts = [
        'daily_budget' => 'decimal:2',
        'lifetime_budget' => 'decimal:2',
        'start_time' => 'datetime',
        'stop_time' => 'datetime',
        'source_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'raw_payload_json' => 'array',
    ];

    public function metaAdAccount(): BelongsTo
    {
        return $this->belongsTo(MetaAdAccount::class);
    }

    public function adSets(): HasMany
    {
        return $this->hasMany(MetaAdSet::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(MetaInsightDaily::class, 'entity_local_id')
            ->where('entity_type', 'campaign');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(CampaignRecommendation::class, 'meta_campaign_id');
    }
}
