<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CampaignDraft extends Model
{
    use HasFactory;

    protected $fillable = [
        'briefing_id',
        'template_id',
        'generated_name',
        'draft_payload_json',
        'status',
        'approved_by',
        'approved_at',
        'review_notes',
        'published_at',
    ];

    protected $casts = [
        'draft_payload_json' => 'array',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function briefing(): BelongsTo
    {
        return $this->belongsTo(CampaignBriefing::class, 'briefing_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CampaignTemplate::class, 'template_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function publishJobs(): HasMany
    {
        return $this->hasMany(PublishJob::class, 'draft_id');
    }

    public function draftEnrichments(): HasMany
    {
        return $this->hasMany(DraftEnrichment::class, 'campaign_draft_id');
    }
}
