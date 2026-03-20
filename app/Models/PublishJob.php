<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'draft_id',
        'provider',
        'action_type',
        'payload_json',
        'status',
        'attempts',
        'response_json',
        'error_message',
        'executed_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'response_json' => 'array',
        'attempts' => 'integer',
        'executed_at' => 'datetime',
    ];

    public function draft(): BelongsTo
    {
        return $this->belongsTo(CampaignDraft::class, 'draft_id');
    }
}
