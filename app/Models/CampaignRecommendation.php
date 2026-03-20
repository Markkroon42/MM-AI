<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_campaign_id',
        'meta_ad_set_id',
        'meta_ad_id',
        'recommendation_type',
        'severity',
        'title',
        'explanation',
        'proposed_action',
        'action_payload_json',
        'source_agent',
        'confidence_score',
        'status',
        'created_by_run_id',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'executed_at',
    ];

    protected $casts = [
        'action_payload_json' => 'array',
        'confidence_score' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MetaCampaign::class, 'meta_campaign_id');
    }

    public function adSet(): BelongsTo
    {
        return $this->belongsTo(MetaAdSet::class, 'meta_ad_set_id');
    }

    public function ad(): BelongsTo
    {
        return $this->belongsTo(MetaAd::class, 'meta_ad_id');
    }

    public function agentRun(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class, 'created_by_run_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getTargetEntityAttribute()
    {
        if ($this->meta_ad_id) {
            return $this->ad;
        }

        if ($this->meta_ad_set_id) {
            return $this->adSet;
        }

        if ($this->meta_campaign_id) {
            return $this->campaign;
        }

        return null;
    }

    public function getTargetTypeAttribute(): string
    {
        if ($this->meta_ad_id) {
            return 'ad';
        }

        if ($this->meta_ad_set_id) {
            return 'ad_set';
        }

        if ($this->meta_campaign_id) {
            return 'campaign';
        }

        return 'unknown';
    }
}
