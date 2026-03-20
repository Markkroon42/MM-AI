<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaAd extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_ad_set_id',
        'meta_ad_id',
        'name',
        'status',
        'effective_status',
        'creative_meta_id',
        'preview_url',
        'source_updated_at',
        'last_synced_at',
        'raw_payload_json',
    ];

    protected $casts = [
        'source_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'raw_payload_json' => 'array',
    ];

    public function adSet(): BelongsTo
    {
        return $this->belongsTo(MetaAdSet::class, 'meta_ad_set_id');
    }

    public function insights(): HasMany
    {
        return $this->hasMany(MetaInsightDaily::class, 'entity_local_id')
            ->where('entity_type', 'ad');
    }
}
