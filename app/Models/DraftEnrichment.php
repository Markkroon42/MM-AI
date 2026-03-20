<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftEnrichment extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_draft_id',
        'ai_usage_log_id',
        'enrichment_type',
        'status',
        'payload_json',
        'created_by',
    ];

    protected $casts = [
        'payload_json' => 'array',
    ];

    public function campaignDraft(): BelongsTo
    {
        return $this->belongsTo(CampaignDraft::class, 'campaign_draft_id');
    }

    public function aiUsageLog(): BelongsTo
    {
        return $this->belongsTo(AiUsageLog::class, 'ai_usage_log_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
