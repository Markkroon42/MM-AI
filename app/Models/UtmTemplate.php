<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtmTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'source',
        'medium',
        'campaign_pattern',
        'content_pattern',
        'term_pattern',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function campaignTemplates(): HasMany
    {
        return $this->hasMany(CampaignTemplate::class, 'default_utm_template_id');
    }
}
