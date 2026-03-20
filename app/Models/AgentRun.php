<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_name',
        'scope_type',
        'scope_id',
        'status',
        'input_payload_json',
        'output_payload_json',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'input_payload_json' => 'array',
        'output_payload_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function recommendations(): HasMany
    {
        return $this->hasMany(CampaignRecommendation::class, 'created_by_run_id');
    }

    public function getDurationAttribute(): ?float
    {
        if ($this->started_at && $this->finished_at) {
            return $this->started_at->diffInSeconds($this->finished_at);
        }

        return null;
    }
}
