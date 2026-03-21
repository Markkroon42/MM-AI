<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'market',
        'objective',
        'funnel_stage',
        'theme',
        'default_budget',
        'default_utm_template_id',
        'landing_page_url',
        'structure_json',
        'creative_rules_json',
        'is_active',
        'meta_account_id',
    ];

    protected $casts = [
        'default_budget' => 'decimal:2',
        'structure_json' => 'array',
        'creative_rules_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function utmTemplate(): BelongsTo
    {
        return $this->belongsTo(UtmTemplate::class, 'default_utm_template_id');
    }

    public function campaignDrafts(): HasMany
    {
        return $this->hasMany(CampaignDraft::class, 'template_id');
    }
}
