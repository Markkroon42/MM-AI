<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BriefingStrategyNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_briefing_id',
        'ai_usage_log_id',
        'strategy_payload_json',
    ];

    protected $casts = [
        'strategy_payload_json' => 'array',
    ];

    public function campaignBriefing(): BelongsTo
    {
        return $this->belongsTo(CampaignBriefing::class, 'campaign_briefing_id');
    }

    public function aiUsageLog(): BelongsTo
    {
        return $this->belongsTo(AiUsageLog::class, 'ai_usage_log_id');
    }
}
