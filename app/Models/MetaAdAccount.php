<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaAdAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'meta_account_id',
        'business_name',
        'currency',
        'timezone_name',
        'status',
        'access_token_reference',
        'is_active',
        'last_synced_at',
        'raw_payload_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'raw_payload_json' => 'array',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(MetaCampaign::class);
    }
}
