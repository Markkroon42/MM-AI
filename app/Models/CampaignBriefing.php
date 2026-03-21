<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignBriefing extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'brand',
        'market',
        'objective',
        'product_name',
        'target_audience',
        'landing_page_url',
        'budget_amount',
        'campaign_goal',
        'notes',
        'status',
        'meta_account_id',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaignDrafts(): HasMany
    {
        return $this->hasMany(CampaignDraft::class, 'briefing_id');
    }

    public function briefingStrategyNotes(): HasMany
    {
        return $this->hasMany(BriefingStrategyNote::class, 'campaign_briefing_id');
    }
}
